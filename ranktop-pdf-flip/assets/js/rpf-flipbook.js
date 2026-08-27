/* global pdfjsLib, St, RPF_Reader */
( function() {
	'use strict';

	if ( typeof pdfjsLib === 'undefined' || typeof St === 'undefined' ) {
		return;
	}

	pdfjsLib.GlobalWorkerOptions.workerSrc = RPF_Reader.workerUrl;

	var i18n = RPF_Reader.i18n || {};

	function t( key, fallback ) {
		return i18n[ key ] || fallback || key;
	}

	/**
	 * One reader instance per .rpf-flipbook container.
	 */
	function Reader( container ) {
		this.container = container;
		this.pdfUrl = container.getAttribute( 'data-pdf-url' );
		this.zoom = 1;
		this.minZoom = 0.6;
		this.maxZoom = 2.2;
		this.renderedPages = {};
		this.pageFlip = null;
		this.pdfDoc = null;

		this.stage = container.querySelector( '.rpf-stage' );
		this.loadingEl = container.querySelector( '.rpf-loading' );
		this.wrapperEl = container.querySelector( '.rpf-book-wrapper' );
		this.bookEl = container.querySelector( '.rpf-book' );
		this.thumbPanel = container.querySelector( '.rpf-thumb-panel' );
		this.currentEl = container.querySelector( '.rpf-current' );
		this.totalEl = container.querySelector( '.rpf-total' );

		this.bindToolbar();

		if ( this.pdfUrl ) {
			this.load();
		} else {
			this.showError();
		}
	}

	Reader.prototype.showError = function() {
		if ( this.loadingEl ) {
			this.loadingEl.textContent = t( 'error', 'Não foi possível carregar o PDF desta edição.' );
			this.loadingEl.classList.add( 'rpf-error' );
		}
	};

	Reader.prototype.load = function() {
		var self = this;

		pdfjsLib.getDocument( this.pdfUrl ).promise.then( function( pdfDoc ) {
			self.pdfDoc = pdfDoc;
			self.buildPages( pdfDoc.numPages );
			self.initFlip( pdfDoc.numPages );
			if ( self.loadingEl ) {
				self.loadingEl.remove();
			}
		} ).catch( function() {
			self.showError();
		} );
	};

	Reader.prototype.buildPages = function( numPages ) {
		var frag = document.createDocumentFragment();

		for ( var i = 1; i <= numPages; i++ ) {
			var page = document.createElement( 'div' );
			page.className = 'rpf-page';
			page.setAttribute( 'data-page-number', i );

			var canvas = document.createElement( 'canvas' );
			page.appendChild( canvas );

			var num = document.createElement( 'span' );
			num.className = 'rpf-page-number';
			num.textContent = i;
			page.appendChild( num );

			frag.appendChild( page );
		}

		this.bookEl.appendChild( frag );

		if ( this.totalEl ) {
			this.totalEl.textContent = numPages;
		}
	};

	Reader.prototype.initFlip = function( numPages ) {
		var self = this;
		var height = parseInt( this.container.getAttribute( 'data-height' ), 10 ) || 700;

		this.pageFlip = new St.PageFlip( this.bookEl, {
			width: Math.round( height * 0.72 ),
			height: height,
			size: 'stretch',
			minWidth: 260,
			maxWidth: 1400,
			minHeight: 360,
			maxHeight: 1800,
			maxShadowOpacity: 0.4,
			showCover: false,
			usePortrait: true,
			mobileScrollSupport: true,
			useMouseEvents: true,
			swipeDistance: 30,
			startPage: 0,
		} );

		this.pageFlip.loadFromHTML( this.bookEl.querySelectorAll( '.rpf-page' ) );

		this.pageFlip.on( 'flip', function( e ) {
			self.onFlip( e.data );
		} );

		// Render current + neighbouring pages up front (progressive rendering).
		this.renderAround( 0, numPages );
		this.updateIndicator( 1 );
	};

	Reader.prototype.onFlip = function( pageIndex ) {
		this.renderAround( pageIndex, this.pdfDoc.numPages );
		this.updateIndicator( pageIndex + 1 );
	};

	Reader.prototype.updateIndicator = function( pageNumber ) {
		if ( this.currentEl ) {
			this.currentEl.textContent = pageNumber;
		}
	};

	Reader.prototype.renderAround = function( centerIndex, numPages ) {
		var offsets = [ 0, 1, -1, 2, -2 ];
		for ( var i = 0; i < offsets.length; i++ ) {
			var pageNumber = centerIndex + offsets[ i ] + 1;
			if ( pageNumber >= 1 && pageNumber <= numPages ) {
				this.renderPage( pageNumber );
			}
		}
	};

	Reader.prototype.renderPage = function( pageNumber ) {
		if ( this.renderedPages[ pageNumber ] ) {
			return;
		}
		this.renderedPages[ pageNumber ] = true;

		var self = this;
		var selector = '.rpf-page[data-page-number="' + pageNumber + '"] canvas';
		var canvas = this.bookEl.querySelector( selector );
		if ( ! canvas ) {
			return;
		}

		this.pdfDoc.getPage( pageNumber ).then( function( page ) {
			var baseViewport = page.getViewport( { scale: 1 } );
			var containerHeight = self.container.getAttribute( 'data-height' ) || 700;
			var scale = ( containerHeight / baseViewport.height ) * 1.5;
			var viewport = page.getViewport( { scale: scale } );

			canvas.width = viewport.width;
			canvas.height = viewport.height;

			var ctx = canvas.getContext( '2d' );
			page.render( { canvasContext: ctx, viewport: viewport } );
		} );
	};

	Reader.prototype.bindToolbar = function() {
		var self = this;
		var toolbar = this.container.querySelector( '.rpf-toolbar' );
		if ( ! toolbar ) {
			return;
		}

		var prev = toolbar.querySelector( '.rpf-prev' );
		var next = toolbar.querySelector( '.rpf-next' );
		var zoomIn = toolbar.querySelector( '.rpf-zoom-in' );
		var zoomOut = toolbar.querySelector( '.rpf-zoom-out' );
		var fullscreen = toolbar.querySelector( '.rpf-fullscreen' );
		var thumbsToggle = toolbar.querySelector( '.rpf-thumbnails' );

		if ( prev ) {
			prev.addEventListener( 'click', function() {
				self.pageFlip && self.pageFlip.flipPrev();
			} );
		}
		if ( next ) {
			next.addEventListener( 'click', function() {
				self.pageFlip && self.pageFlip.flipNext();
			} );
		}
		if ( zoomIn ) {
			zoomIn.addEventListener( 'click', function() {
				self.setZoom( self.zoom + 0.2 );
			} );
		}
		if ( zoomOut ) {
			zoomOut.addEventListener( 'click', function() {
				self.setZoom( self.zoom - 0.2 );
			} );
		}
		if ( fullscreen ) {
			fullscreen.addEventListener( 'click', function() {
				self.toggleFullscreen();
			} );
		}
		if ( thumbsToggle ) {
			thumbsToggle.addEventListener( 'click', function() {
				self.toggleThumbnails();
			} );
		}

		this.container.setAttribute( 'tabindex', '0' );
		this.container.addEventListener( 'keydown', function( e ) {
			if ( ! self.pageFlip ) {
				return;
			}
			if ( e.key === 'ArrowRight' ) {
				self.pageFlip.flipNext();
			} else if ( e.key === 'ArrowLeft' ) {
				self.pageFlip.flipPrev();
			} else if ( e.key === 'Home' ) {
				self.pageFlip.turnToPage( 0 );
			} else if ( e.key === 'End' ) {
				self.pageFlip.turnToPage( self.pdfDoc.numPages - 1 );
			}
		} );
	};

	Reader.prototype.setZoom = function( value ) {
		this.zoom = Math.min( this.maxZoom, Math.max( this.minZoom, value ) );
		this.wrapperEl.style.transform = 'scale(' + this.zoom + ')';
	};

	Reader.prototype.toggleFullscreen = function() {
		if ( document.fullscreenElement ) {
			document.exitFullscreen();
			return;
		}
		if ( this.container.requestFullscreen ) {
			this.container.requestFullscreen();
		}
	};

	Reader.prototype.toggleThumbnails = function() {
		var self = this;
		var isHidden = this.thumbPanel.hasAttribute( 'hidden' );

		if ( isHidden ) {
			this.buildThumbnails();
			this.thumbPanel.removeAttribute( 'hidden' );
		} else {
			this.thumbPanel.setAttribute( 'hidden', '' );
		}

		var btn = this.container.querySelector( '.rpf-thumbnails' );
		if ( btn ) {
			btn.setAttribute( 'aria-pressed', isHidden ? 'true' : 'false' );
		}
	};

	Reader.prototype.buildThumbnails = function() {
		if ( this.thumbPanel.childElementCount > 0 || ! this.pdfDoc ) {
			return;
		}

		var self = this;
		var numPages = this.pdfDoc.numPages;

		var _loop = function( pageNumber ) {
			var thumbBtn = document.createElement( 'button' );
			thumbBtn.type = 'button';
			thumbBtn.className = 'rpf-thumb';
			thumbBtn.setAttribute( 'aria-label', t( 'page', 'Página' ) + ' ' + pageNumber );

			var canvas = document.createElement( 'canvas' );
			thumbBtn.appendChild( canvas );

			var label = document.createElement( 'span' );
			label.textContent = pageNumber;
			thumbBtn.appendChild( label );

			thumbBtn.addEventListener( 'click', function() {
				self.pageFlip.turnToPage( pageNumber - 1 );
			} );

			self.thumbPanel.appendChild( thumbBtn );

			self.pdfDoc.getPage( pageNumber ).then( function( page ) {
				var viewport = page.getViewport( { scale: 0.2 } );
				canvas.width = viewport.width;
				canvas.height = viewport.height;
				page.render( { canvasContext: canvas.getContext( '2d' ), viewport: viewport } );
			} );
		};

		for ( var pageNumber = 1; pageNumber <= numPages; pageNumber++ ) {
			_loop( pageNumber );
		}
	};

	function initAll() {
		var containers = document.querySelectorAll( '.rpf-flipbook' );
		containers.forEach( function( container ) {
			if ( container.rpfInitialized ) {
				return;
			}
			container.rpfInitialized = true;
			new Reader( container );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initAll );
	} else {
		initAll();
	}
} )();
