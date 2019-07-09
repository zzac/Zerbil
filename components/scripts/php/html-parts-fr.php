<?php function html_parts_head($title, $description, ...$css_arr) { ?>
    <head>
        <meta charset="utf-8">
        <meta name="description" content="<?php echo $description; ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="theme-color" content="#140747">
        <link rel="stylesheet" type="text/css" href="/components/css/pace.css">
        <link rel="stylesheet" type="text/css" href="/components/css/page-header.css">
        <link rel="stylesheet" type="text/css" href="/components/css/page-footer.css">
        <link rel="stylesheet" type="text/css" href="/components/css/general.css">
        <?php foreach ($css_arr as $css): ?><link rel="stylesheet" type="text/css" href="/components/css/<?php echo $css; ?>.css"><?php endforeach; ?>
        <script type="text/javascript" src="/components/scripts/js/pace.js"></script>
        <link rel="icon" type="image/png" sizes="16x16" href="/components/img/favicons/favicon-16x16.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/components/img/favicons/favicon-32x32.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/components/img/favicons/apple-touch-icon.png">
        <link rel="manifest" href="/fr/manifest.json">
        <link rel="mask-icon" href="/components/img/favicons/safari-pinned-tab.svg" color="#c41bc4">
        <link rel="shortcut icon" href="/components/img/favicons/favicon.ico">
        <meta name="msapplication-TileColor" content="#0d052e">
        <meta name="msapplication-config" content="/components/img/favicons/browserconfig.xml">
        <title><?php echo $title." - Zerbil"; ?></title>
    </head>
<?php } ?>
<?php function html_parts_header($active = "") { ?>
    <header id="page-header">
        <div id="site-name">
            <h1>Zerbil</h1>
            <img src="/components/img/logos/zerbil-32.png" alt="Zerbil logo">
        </div>
        <nav>
            <?php foreach (array("home" => "Accueil", "riddles" => "Énigmes", "account" => "Compte") as $key => $anchor): ?>
                <a <?php if ($active == $key): ?>class="active"<?php endif; ?> href="/fr/<?php echo ($key != "home") ? $key : ""; ?>"><?php echo $anchor; ?></a>
            <?php endforeach; ?>
        </nav>
        <hr>
    </header>
<?php } ?>
<?php function html_parts_footer() { ?>
    <footer id="page-footer">
    	<span class="site-info">Zerbil est une organisation à but non lucratif.</span>
        <span class="site-info">Contact : zcb@zerbil.org</span>
        <span class="site-info">2019</span>
    </footer>
<?php } ?>