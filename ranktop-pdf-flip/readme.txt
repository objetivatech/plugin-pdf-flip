=== Ranktop PDF Flip ===
Contributors: ranktop
Tags: pdf, flipbook, jornal, revista, edicoes
Requires at least: 5.9
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gerencia edições periódicas (jornal/revista) em PDF com um leitor flipbook standalone.

== Description ==

Ranktop PDF Flip cria um tipo de conteúdo "Edições" para publicar jornais, revistas ou
boletins periódicos em PDF, com um leitor no formato flipbook (efeito de virar página)
totalmente embutido no plugin — sem serviços externos, sem login, sem assinatura e sem
conversão de PDF em imagem no servidor.

**Recursos**

* CPT "Edições" com número, título, data, capa e arquivo PDF independentes
* Campo "Edição em destaque" para escolher manualmente a edição destacada
* Arquivo público (`/edicoes/`) com filtro por ano/mês e paginação
* Página individual de cada edição com o leitor embutido
* Leitor com efeito de página (StPageFlip) renderizando o PDF diretamente via PDF.js
  (sem pré-conversão em imagens), com página dupla no desktop e página única no mobile,
  zoom, fullscreen, miniaturas, atalhos de teclado, toque/swipe e download do PDF original
* Shortcode `[edicao id="123" height="700"]` para inserir uma edição em qualquer lugar
* Shortcode `[edicao_ultima estilo="card|banner"]` para destacar a última edição, com
  aparência (cor de fundo, borda, cores de fonte, formato da capa) configurável em
  Edições → Configurações
* Shortcode `[edicoes]` — biblioteca/estante com todas as edições, para embutir em
  qualquer página, com filtro de ano/mês, colunas e ordenação configuráveis
* Página de configurações do plugin (Edições → Configurações) para o layout do card
  de destaque e os padrões da biblioteca de edições

== Installation ==

1. Envie a pasta `ranktop-pdf-flip` para `/wp-content/plugins/`.
2. Ative o plugin no painel do WordPress.
3. Vá em "Edições" no menu do admin e cadastre a primeira edição (número, capa, PDF).
4. Acesse `/edicoes/` para ver o arquivo, ou use os shortcodes `[edicao]`, `[edicao_ultima]`
   e `[edicoes]`.
5. Ajuste a aparência do card e da biblioteca em Edições → Configurações.

== Changelog ==

= 1.1.0 =
* Nova página de configurações (Edições → Configurações): cor de fundo, borda, cores de
  fonte, formato da capa e layout padrão do card `[edicao_ultima]`; colunas, quantidade,
  filtro e ordenação padrão da biblioteca.
* Novo shortcode `[edicoes]`: biblioteca/estante com todas as edições, para embutir em
  qualquer página do site.

= 1.0.0 =
* Versão inicial: CPT de edições, leitor flipbook, arquivo com filtros e shortcodes.
