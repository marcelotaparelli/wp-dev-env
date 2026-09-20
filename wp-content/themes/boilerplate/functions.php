<?php
/**
 * Carrega a fonte Inter, usada em todo o design.
 *
 * Usando Google Fonts por simplicidade agora. Antes de ir pra produção,
 * o ideal é trocar por auto-hospedagem (baixar os .woff2 da Inter e
 * declarar via "fontFace" no theme.json) — evita uma requisição
 * externa a mais e é mais alinhado com LGPD (não manda IP do visitante
 * pro Google a cada carregamento de página).
 */
function boilerplate_fonts() {
	wp_enqueue_style(
		'boilerplate-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
		array(),
		null
	);
}
add_action( 'wp_enqueue_scripts', 'boilerplate_fonts' );

/**
 * Setup do tema.
 */
function boilerplate_setup() {
	// Deixa o WordPress gerenciar o <title> automaticamente.
	add_theme_support( 'title-tag' );

	// Habilita imagem destacada.
	add_theme_support( 'post-thumbnails' );

	// Usa o menu de navegação editável no Site Editor (wp:navigation).
	add_theme_support( 'wp-block-styles' );
}
add_action( 'after_setup_theme', 'boilerplate_setup' );

/**
 * Carrega CSS/JS próprios do tema, além do que o theme.json já resolve.
 * Use isso pra regras que fogem do design system (layout muito específico
 * de um componente, por exemplo), não pra reimplementar o theme.json aqui.
 */
function boilerplate_assets() {
	$theme_version = wp_get_theme()->get( 'Version' );

	wp_enqueue_style(
		'boilerplate-style',
		get_theme_file_uri( 'assets/css/main.css' ),
		array(),
		$theme_version
	);
}
add_action( 'wp_enqueue_scripts', 'boilerplate_assets' );