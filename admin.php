<?php
require_once __DIR__ . '/inc/auth.php';

/* Numéro de version de cette interface d'admin — visible uniquement
 * ici (jamais sur le site public), pour repérer facilement quelle
 * version est actuellement déployée sur le serveur. À incrémenter à
 * la main lors des évolutions notables de admin.php. */
const MOMOX_ADMIN_VERSION = '1.1';

// Déconnexion
if (isset($_GET['logout'])) {
    momox_logout();
    header('Location: admin.php');
    exit;
}

// Traitement du formulaire de connexion
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password'])) {
    $loginResult = momox_login($_POST['admin_password']);
    if ($loginResult['ok']) {
        header('Location: admin.php');
        exit;
    }
    if ($loginResult['locked']) {
        $loginError = 'Trop de tentatives — réessaie dans ' . $loginResult['retry_after'] . ' secondes.';
    } else {
        $loginError = 'Mot de passe incorrect. Réessaie.';
    }
}

// Page de connexion tant que le mot de passe n'a pas été saisi
if (!momox_is_logged_in()):
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Momox Dogs — Connexion admin</title>
<meta name="robots" content="noindex, nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,600;0,9..144,700&family=Work+Sans:wght@400;500;600&display=swap">
<style>
  :root{
    --ink:#3d352c; --paper:#fbf7ec; --paper-soft:#f4ecd8; --gold:#ab8f66; --line:rgba(61,53,44,.14);
    --font-display:"Fraunces",Georgia,serif; --font-body:"Work Sans",-apple-system,sans-serif;
  }
  *{box-sizing:border-box;}
  body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--paper);font-family:var(--font-body);color:var(--ink);padding:20px;}
  .login-card{width:100%;max-width:380px;background:var(--paper-soft);border:1px solid var(--line);border-radius:18px;padding:32px 28px;box-shadow:0 12px 32px rgba(61,53,44,.08);}
  .login-card h1{font-family:var(--font-display);font-size:1.4rem;margin:0 0 6px;}
  .login-card p{margin:0 0 22px;font-size:.9rem;color:#6b6252;}
  .login-card label{display:block;font-size:.85rem;font-weight:600;margin-bottom:6px;}
  .login-card input{width:100%;padding:12px 14px;border:1px solid var(--line);border-radius:10px;font-size:1rem;font-family:inherit;background:#fff;}
  .login-card input:focus{outline:2px solid var(--gold);outline-offset:1px;}
  .login-card button{width:100%;margin-top:16px;padding:13px;border:none;border-radius:10px;background:var(--gold);color:#fff;font-size:1rem;font-weight:600;font-family:inherit;cursor:pointer;}
  .login-card button:hover{opacity:.92;}
  .login-error{margin-top:14px;padding:10px 12px;border-radius:8px;background:#f3ddd6;color:#8a3a28;font-size:.85rem;}
</style>
</head>
<body>
  <form class="login-card" method="post" action="admin.php">
    <h1>Momox Dogs</h1>
    <p>Espace d'édition du site — accès protégé par mot de passe.</p>
    <label for="admin_password">Mot de passe</label>
    <input type="password" id="admin_password" name="admin_password" autofocus required>
    <button type="submit">Se connecter</button>
    <?php if ($loginError): ?>
      <div class="login-error"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <div style="margin-top:16px; text-align:center; font-size:.72rem; color:#a89a83;">v<?= htmlspecialchars(MOMOX_ADMIN_VERSION, ENT_QUOTES, 'UTF-8') ?></div>
  </form>
</body>
</html>
<?php
exit;
endif;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Momox Dogs — Édition du site</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="images/icons/favicon.ico" sizes="any">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,500&family=Work+Sans:wght@400;500;600;700&display=swap" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,500&family=Work+Sans:wght@400;500;600;700&display=swap"></noscript>

<style>
/* =====================================================================
   PALETTE — copiée telle quelle depuis index.html pour garder le même
   look and feel. Si tu changes le thème dans content.json → theme,
   ces valeurs par défaut servent d'aperçu tant que le fichier n'est pas
   chargé, puis sont réappliquées automatiquement depuis le JSON chargé.
   ===================================================================== */
:root{
  --ink:        #3d352c;
  --paper:      #fbf7ec;
  --paper-soft: #f4ecd8;
  --pine-dark:  #8c8068;
  --pine:       #b8a47c;
  --olive:      #8f8268;
  --gold:       #ab8f66;
  --gold-light: #ddd0b0;
  --cream:      #fffaf0;

  --ink-rgb:       61,53,44;
  --pine-dark-rgb: 140,128,104;
  --cream-rgb:     255,250,240;
  --paper-rgb:     251,247,236;

  --line:       rgba(var(--ink-rgb),0.14);
  --line-dark:  rgba(var(--cream-rgb),0.16);

  --font-display: "Fraunces", Georgia, serif;
  --font-body: "Work Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;

  --radius: 18px;
  --container: 1120px;

  --danger: #b45341;
  --danger-light: #f3ddd6;
  --ok: #5f7a4f;
}

*,*::before,*::after{ box-sizing:border-box; }
html{ -webkit-text-size-adjust:100%; scroll-behavior:smooth; }
@media (prefers-reduced-motion: reduce){
  html{ scroll-behavior:auto; }
  *,*::before,*::after{ animation-duration:.001ms !important; animation-iteration-count:1 !important; transition-duration:.001ms !important; }
}
body{
  margin:0;
  font-family:var(--font-body);
  color:var(--ink);
  background:var(--paper);
  line-height:1.55;
  -webkit-font-smoothing:antialiased;
  padding-bottom:190px; /* repli initial — recalculé précisément en JS via syncActionbarSpacing() dès que la barre est mesurable, pour s'adapter au nombre de lignes réellement affichées */
  position:relative;
}
/* Fond décoratif discret — deux halos très doux qui dérivent lentement,
   purement esthétiques (aucune interaction, aria-hidden via -webkit). */
body::before, body::after{
  content:""; position:fixed; z-index:0; pointer-events:none; border-radius:50%;
  filter:blur(60px); opacity:.35; will-change:transform;
}
body::before{
  width:46vw; height:46vw; max-width:560px; max-height:560px;
  top:-14vw; right:-12vw;
  background:radial-gradient(circle, rgba(var(--pine-dark-rgb),.35), transparent 70%);
  animation:driftA 26s ease-in-out infinite;
}
body::after{
  width:38vw; height:38vw; max-width:460px; max-height:460px;
  bottom:-10vw; left:-10vw;
  background:radial-gradient(circle, rgba(171,143,102,.3), transparent 70%);
  animation:driftB 32s ease-in-out infinite;
}
@keyframes driftA{ 0%,100%{ transform:translate(0,0) scale(1); } 50%{ transform:translate(-3vw,3vw) scale(1.08); } }
@keyframes driftB{ 0%,100%{ transform:translate(0,0) scale(1); } 50%{ transform:translate(3vw,-2vw) scale(1.1); } }
.admin-header, main#appMain, .actionbar, #emptyState{ position:relative; z-index:1; }
img{ max-width:100%; display:block; }
a{ color:inherit; }
h1,h2,h3{ font-family:var(--font-display); margin:0 0 .3em; font-weight:600; line-height:1.15; }
p{ margin:0 0 1em; }
.container{ width:100%; max-width:var(--container); margin:0 auto; padding:0 clamp(16px,4.5vw,24px); }
/* Grands écrans (2K et plus) : la colonne centrale s'élargit par paliers
   plutôt que de rester bloquée à 1120px avec de grandes marges vides. */
@media (min-width:1440px){ :root{ --container:1320px; } }
@media (min-width:1800px){ :root{ --container:1520px; } }
@media (min-width:2200px){ :root{ --container:1700px; } }
.eyebrow{
  font-family:var(--font-body); font-weight:700; letter-spacing:.12em; text-transform:uppercase;
  font-size:.72rem; color:var(--gold);
}

/* ---------------------------------------------------------------------
   Boutons — repris du site principal
   --------------------------------------------------------------------- */
.btn{
  display:inline-flex; align-items:center; justify-content:center; gap:8px; white-space:nowrap;
  font-family:var(--font-body); font-weight:600; font-size:.94rem;
  padding:12px 22px; border-radius:999px; border:2px solid transparent;
  text-decoration:none; cursor:pointer; transition:transform .18s cubic-bezier(.34,1.56,.64,1), box-shadow .18s ease, opacity .15s ease;
  min-height:44px; position:relative; overflow:hidden;
}
.btn:hover{ transform:translateY(-2px); }
.btn:active{ transform:scale(.97); }
.btn-primary{
  background:linear-gradient(135deg,var(--gold-light) 0%,var(--gold) 100%); background-size:160% 160%; background-position:0% 50%;
  color:#3d2c0f; transition:transform .18s cubic-bezier(.34,1.56,.64,1), box-shadow .18s ease, background-position .4s ease;
}
.btn-primary:hover{ box-shadow:0 10px 22px rgba(193,147,44,.45); background-position:100% 50%; }
.btn-outline{ background:transparent; border-color:var(--line); color:var(--ink); }
.btn-outline:hover{ border-color:var(--gold); color:var(--gold); }
.btn-danger{ background:transparent; border-color:var(--danger-light); color:var(--danger); }
.btn-danger:hover{ background:var(--danger-light); }
.btn-sm{ padding:8px 14px; font-size:.82rem; min-height:36px; }
.btn-icon{ padding:8px; min-width:36px; min-height:36px; border-radius:50%; }
.btn:disabled{ opacity:.45; cursor:not-allowed; transform:none; box-shadow:none; }
/* Reflet lumineux au survol des boutons pleins — purement décoratif,
   ::before non interactif (pointer-events:none), clippé par l'overflow
   hidden du bouton donc jamais visible en dehors de sa pilule. */
.btn-primary::before{
  content:''; position:absolute; inset:0; pointer-events:none;
  background:linear-gradient(115deg, transparent 40%, rgba(255,255,255,.65) 50%, transparent 60%);
  background-size:250% 100%; background-position:150% 0;
  transition:background-position .65s cubic-bezier(.2,.8,.2,1);
}
.btn-primary:hover::before{ background-position:-50% 0; }
/* Onde tactile au clic — ajoutée/retirée en JS (voir POLISH VISUEL),
   ne bloque jamais l'interaction (pointer-events:none) et se nettoie
   toute seule après son animation. */
.btn-ripple{
  position:absolute; border-radius:50%; transform:scale(0); opacity:.55; pointer-events:none;
  background:rgba(255,255,255,.6); animation:btnRippleFx .55s ease-out forwards;
}
.btn-outline .btn-ripple, .btn-danger .btn-ripple{ background:rgba(var(--ink-rgb),.16); }
@keyframes btnRippleFx{ to{ transform:scale(2.6); opacity:0; } }

/* ---------------------------------------------------------------------
   En-tête
   --------------------------------------------------------------------- */
.admin-header{
  position:sticky; top:0; z-index:50;
  background:rgba(var(--paper-rgb),.97); backdrop-filter:blur(8px);
  border-bottom:1px solid var(--line);
  transform:translateY(-100%);
  animation:headerSlideIn .5s cubic-bezier(.2,.8,.2,1) .05s forwards;
  transition:box-shadow .25s ease, border-color .25s ease;
}
.admin-header.is-scrolled{ box-shadow:0 8px 24px rgba(var(--ink-rgb),.08); }
@keyframes headerSlideIn{ to{ transform:translateY(0); } }
.admin-header .row{ display:flex; align-items:center; gap:14px; padding:14px 0; flex-wrap:wrap; }
.admin-brand{ display:flex; align-items:center; gap:10px; margin-right:auto; }
.admin-brand-mark{
  width:34px; height:34px; border-radius:50%; background:var(--gold-light); flex:none;
  display:flex; align-items:center; justify-content:center; font-family:var(--font-display); font-weight:700; color:#5a4726;
  animation:brandPop .6s cubic-bezier(.34,1.56,.64,1) .15s both;
  transition:transform .25s cubic-bezier(.34,1.56,.64,1), box-shadow .25s ease;
}
.admin-brand:hover .admin-brand-mark{ transform:rotate(-8deg) scale(1.08); box-shadow:0 6px 16px rgba(171,143,102,.4); }
@keyframes brandPop{ from{ transform:scale(.3) rotate(-20deg); opacity:0; } to{ transform:scale(1) rotate(0); opacity:1; } }
.admin-brand-title{ font-family:var(--font-display); font-weight:600; font-size:1.15rem; line-height:1.1; }
.admin-brand-sub{ font-size:.72rem; color:var(--olive); text-transform:uppercase; letter-spacing:.08em; }
.admin-version{ opacity:.75; }
.status-pill{
  font-size:.76rem; font-weight:600; padding:6px 12px; border-radius:999px;
  background:var(--paper-soft); color:var(--olive); border:1px solid var(--line);
  display:flex; align-items:center; gap:6px;
  transition:background .3s ease, color .3s ease, border-color .3s ease, transform .3s cubic-bezier(.34,1.56,.64,1);
}
.status-pill.dirty{ background:#fdf1de; color:#8a5a1c; border-color:#eccd9a; animation:pillBump .35s cubic-bezier(.34,1.56,.64,1); }
.status-pill.dirty::before{ content:"●"; color:#c98a2c; animation:pulseDot 1.8s ease-in-out infinite; }
.status-pill.saved::before{ content:"✓"; }
@keyframes pillBump{ 0%{ transform:scale(1); } 40%{ transform:scale(1.08); } 100%{ transform:scale(1); } }
/* Indicateur global "quelles sections, combien de changements" — vue
   d'ensemble avant de tout déplier, cliquable pour ouvrir l'aperçu
   détaillé (même modale que la comparaison de publication). Largeur
   plafonnée avec retour à la ligne : la liste de sections peut être un
   peu longue (jusqu'à 4 noms), pas la peine de forcer un pavé démesuré. */
#globalDiffIndicator{
  cursor:pointer; background:rgba(171,143,102,.16); color:#7a5f38; border-color:var(--gold-light);
  font-weight:700; max-width:min(480px, 82vw); text-align:left; line-height:1.4;
}
#globalDiffIndicator:hover{ background:rgba(171,143,102,.26); }
#globalDiffIndicator::before{ content:"✎"; }
/* Barre de recherche/filtre des anciennes versions */
.bkp-search{ margin-bottom:14px; }
.bkp-search input[type=search]{ width:100%; }
.bkp-search-empty{ font-size:.85rem; color:var(--olive); padding:10px 2px; }
@keyframes pulseDot{ 0%,100%{ opacity:1; } 50%{ opacity:.35; } }

/* ---------------------------------------------------------------------
   Mise en page générale
   --------------------------------------------------------------------- */
.intro{ padding:26px 0 6px; }
.intro h1{ font-size:1.6rem; }
.intro p{ color:var(--olive); }
.panels-toolbar{ display:flex; gap:10px; flex-wrap:wrap; margin-top:16px; }

.panel{
  background:#fff; border:1px solid var(--line); border-radius:var(--radius);
  margin-bottom:20px; overflow:hidden;
  transition:box-shadow .25s ease, border-color .25s ease, transform .25s ease;
  opacity:0; transform:translateY(16px);
}
main#appMain.is-ready .panel{ animation:panelIn .55s cubic-bezier(.2,.8,.2,1) both; }
main#appMain.is-ready .panel:nth-of-type(1){ animation-delay:.02s; }
main#appMain.is-ready .panel:nth-of-type(2){ animation-delay:.06s; }
main#appMain.is-ready .panel:nth-of-type(3){ animation-delay:.10s; }
main#appMain.is-ready .panel:nth-of-type(4){ animation-delay:.14s; }
main#appMain.is-ready .panel:nth-of-type(5){ animation-delay:.18s; }
main#appMain.is-ready .panel:nth-of-type(6){ animation-delay:.22s; }
main#appMain.is-ready .panel:nth-of-type(7){ animation-delay:.26s; }
main#appMain.is-ready .panel:nth-of-type(8){ animation-delay:.30s; }
main#appMain.is-ready .panel:nth-of-type(9){ animation-delay:.34s; }
main#appMain.is-ready .panel:nth-of-type(10){ animation-delay:.38s; }
main#appMain.is-ready .panel:nth-of-type(11){ animation-delay:.42s; }
main#appMain.is-ready .panel:nth-of-type(12){ animation-delay:.46s; }
@keyframes panelIn{ from{ opacity:0; transform:translateY(16px); } to{ opacity:1; transform:translateY(0); } }
.panel:hover{ border-color:rgba(var(--pine-dark-rgb),.35); }
.panel.open{ box-shadow:0 4px 22px rgba(var(--ink-rgb),.06); }
.panel-head{
  display:flex; align-items:center; justify-content:space-between; gap:10px;
  padding:18px 22px; cursor:pointer; user-select:none; background:var(--paper-soft);
  transition:background .2s ease;
}
.panel-head:hover{ background:var(--gold-light); }
.panel-head h2{ font-size:1.05rem; margin:0; }
.panel-head .hint{ font-size:.8rem; color:var(--olive); font-weight:400; font-family:var(--font-body); display:block; margin-top:2px; }
.panel-head-text{ min-width:0; flex:1 1 auto; }
.panel-head-right{ display:flex; align-items:center; gap:10px; flex:none; }
.panel-chevron{ transition:transform .3s cubic-bezier(.4,0,.2,1); flex:none; color:var(--olive); }
.panel.open .panel-chevron{ transform:rotate(180deg); }
.panel-diff-badge{
  display:none; align-items:center; gap:6px; white-space:nowrap;
  font-size:.8rem; font-weight:800; letter-spacing:.01em;
  color:#7a3d12; background:#fde3c2; border:1.5px solid #f0a942;
  border-radius:999px; padding:5px 12px 5px 9px; line-height:1.3;
  box-shadow:0 2px 8px rgba(230,140,20,.28);
  animation:bkpBadgeIn .3s cubic-bezier(.34,1.56,.64,1) both;
}
.panel-diff-badge::before{ content:"●"; font-size:.6rem; color:#e8890a; animation:pulseDot 1.8s ease-in-out infinite; }
/* Le panneau lui-même se distingue aussi, visible même quand replié
   et même après avoir scrollé au-delà de son en-tête. */
.panel.has-changes{ border-color:#f0a942; box-shadow:0 4px 18px rgba(230,140,20,.16); }
.panel.has-changes .panel-head{ background:linear-gradient(180deg, #fdf1de 0%, var(--paper-soft) 100%); }
@keyframes bkpBadgeIn{ from{ transform:scale(.75); opacity:0; } to{ transform:scale(1); opacity:1; } }
@media(max-width:480px){
  .panel-head{ flex-wrap:wrap; }
  .panel-head-text{ flex-basis:100%; }
  .panel-head-right{ margin-left:auto; }
}
.panel-body{
  padding:22px; display:none; overflow:hidden; max-height:0; opacity:0;
  transition:max-height .4s cubic-bezier(.4,0,.2,1), opacity .3s ease .05s;
}

/* ---------------------------------------------------------------------
   Interrupteurs (switches) — utilisés pour la visibilité de blocs entiers
   (panneau « Visibilité du site ») et pour masquer un cours en particulier
   dans la liste des prestations.
   --------------------------------------------------------------------- */
.switch{ position:relative; display:inline-flex; align-items:center; flex:none; cursor:pointer; }
.switch input{ position:absolute; inset:0; opacity:0; margin:0; cursor:pointer; }
.switch-track{
  width:46px; height:27px; border-radius:999px; flex:none;
  background:rgba(var(--ink-rgb),.16); border:1px solid var(--line);
  transition:background .22s ease, border-color .22s ease; position:relative;
}
.switch-thumb{
  position:absolute; top:2px; left:2px; width:21px; height:21px; border-radius:50%;
  background:var(--cream); box-shadow:0 1px 3px rgba(var(--ink-rgb),.35);
  transition:transform .22s cubic-bezier(.34,1.56,.64,1);
}
.switch input:checked ~ .switch-track{ background:linear-gradient(135deg,var(--gold-light) 0%,var(--gold) 100%); border-color:transparent; }
.switch input:checked ~ .switch-track .switch-thumb{ transform:translateX(19px); }
.switch input:focus-visible ~ .switch-track{ outline:2px solid var(--gold); outline-offset:2px; }
.switch input:disabled ~ .switch-track{ opacity:.5; cursor:not-allowed; }

.visibility-grid{ display:grid; gap:10px; }
.visibility-row{
  display:flex; align-items:center; justify-content:space-between; gap:16px;
  padding:13px 16px; border:1px solid var(--line); border-radius:14px;
  background:var(--cream); transition:opacity .22s ease, border-color .22s ease, background .22s ease;
}
.visibility-row-text{ display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.visibility-row-text strong{ font-size:.93rem; font-weight:600; }
.visibility-row-text .desc{ font-size:.78rem; color:var(--olive); margin:2px 0 0; flex-basis:100%; }
.visibility-off-pill{
  font-size:.66rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em;
  color:var(--danger); background:var(--danger-light); padding:2px 8px; border-radius:999px;
  display:none;
}
.visibility-row.is-off{ background:transparent; border-style:dashed; border-color:rgba(var(--ink-rgb),.22); }
.visibility-row.is-off .visibility-off-pill{ display:inline-block; }
.visibility-row.is-off .visibility-row-text strong{ color:var(--olive); }

.field{ margin-bottom:16px; }
.field:last-child{ margin-bottom:0; }
.field label{
  display:block; font-size:.82rem; font-weight:600; color:var(--olive); margin-bottom:6px;
  transition:color .2s ease;
}
.field:focus-within label{ color:#7a5f38; }
.field .desc{ font-size:.76rem; color:#8a8171; margin-top:4px; }
.field-counter{ font-size:.72rem; font-weight:600; margin-top:5px; color:var(--olive); }
.field-counter.is-warn{ color:#b8781f; }
.grid-2{ display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.grid-3{ display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; }
@media (max-width:640px){ .grid-2, .grid-3{ grid-template-columns:1fr; } }

/* Champs de texte — base repensée : coins plus doux, halo de focus plus
   marqué avec un léger soulèvement, liseré discret à gauche dès qu'un
   champ contient une valeur (repère visuel rapide sur un long formulaire
   pour voir d'un coup d'œil ce qui est déjà rempli), et un placeholder
   clairement distinct du texte réellement saisi. */
input[type=text], input[type=email], input[type=tel], input[type=url], input[type=search], textarea{
  width:100%; font-family:var(--font-body); font-size:.95rem; color:var(--ink);
  border:1.5px solid var(--line); border-radius:12px; padding:11px 13px;
  background:#fffdf9; box-shadow:0 1px 2px rgba(var(--ink-rgb),.03);
  transition:border-color .2s ease, box-shadow .25s cubic-bezier(.2,.8,.2,1), transform .2s cubic-bezier(.2,.8,.2,1), background .2s ease;
}
input::placeholder, textarea::placeholder{ color:#a99f8a; font-style:italic; }
input:hover, textarea:hover{ border-color:rgba(var(--ink-rgb),.28); }
input:focus, textarea:focus{
  outline:none; border-color:var(--gold); background:#fff;
  box-shadow:0 0 0 4px rgba(171,143,102,.16), 0 4px 12px rgba(var(--ink-rgb),.06);
  transform:translateY(-1px);
}
textarea{ resize:vertical; min-height:90px; line-height:1.55; font-family:var(--font-body); overflow:hidden; }
textarea.tall{ min-height:200px; }
/* Auto-agrandissement (voir autoGrowTextarea en JS) : la hauteur suit le
   texte au fil de la frappe, plus besoin de tirer manuellement le coin
   pour voir ce qu'on écrit — la poignée de redimensionnement reste
   disponible en secours si besoin d'un ajustement fin. */
input.invalid, textarea.invalid{ border-color:var(--danger); background:#fff8f6; border-left-color:var(--danger); }
input.invalid:focus, textarea.invalid:focus{ box-shadow:0 0 0 4px rgba(180,83,65,.16), 0 4px 12px rgba(var(--ink-rgb),.06); }
/* Confirmation positive : dès qu'un champ devient valide (et n'est pas
   vide — pas de coche sur un champ optionnel resté vide), la bordure
   passe au vert et une brève onde lumineuse pulse une seule fois, au
   moment précis où le champ redevient correct — pas à chaque frappe
   suivante tant qu'il le reste (voir setFieldError en JS). */
input.valid, textarea.valid{ border-color:var(--ok); }
input.valid.valid-pulse, textarea.valid.valid-pulse{ animation:fieldValidPop .5s cubic-bezier(.34,1.56,.64,1); }
@keyframes fieldValidPop{
  0%{ box-shadow:0 0 0 0 rgba(95,122,79,.4); }
  60%{ box-shadow:0 0 0 9px rgba(95,122,79,0); }
  100%{ box-shadow:0 0 0 0 rgba(95,122,79,0); }
}
/* Petit tremblement au moment où une erreur apparaît (perte de focus sur
   un champ invalide) — un signal plus vif qu'un simple changement de
   couleur, sans être agressif (amplitude réduite par rapport à la même
   animation utilisée sur les modales de confirmation). */
input.invalid-shake, textarea.invalid-shake{ animation:fieldShake .4s ease; }
@keyframes fieldShake{
  0%,100%{ transform:translateX(0); }
  20%{ transform:translateX(-5px); }
  40%{ transform:translateX(4px); }
  60%{ transform:translateX(-3px); }
  80%{ transform:translateX(2px); }
}
.field-error{
  display:none; font-size:.78rem; font-weight:600; color:var(--danger); margin-top:6px;
  align-items:flex-start; gap:6px; line-height:1.4;
}
.field-error.show{ display:flex; animation:fieldErrorIn .2s cubic-bezier(.2,.8,.2,1); }
.field-error::before{ content:"⚠"; flex:none; }
@keyframes fieldErrorIn{ from{ opacity:0; transform:translateY(-3px); } to{ opacity:1; transform:translateY(0); } }
/* Suggestion non bloquante (ex. lien Facebook qui ne pointe pas vers
   facebook.com) — même famille visuelle que .field-error mais en ton
   "attention" plutôt que "erreur", n'empêche jamais la publication. */
.field-hint{
  display:none; font-size:.78rem; font-weight:600; color:#b8781f; margin-top:6px;
  align-items:flex-start; gap:6px; line-height:1.4;
}
.field-hint.show{ display:flex; animation:fieldErrorIn .2s cubic-bezier(.2,.8,.2,1); }
.field-hint::before{ content:"💡"; flex:none; }
@media (prefers-reduced-motion: reduce){
  input.valid-pulse, textarea.valid-pulse, input.invalid-shake, textarea.invalid-shake{ animation:none; }
}

/* Bouton "effacer" (✕) qui apparaît dans un champ texte dès qu'il
   contient quelque chose — évite d'avoir à tout sélectionner puis
   Suppr pour vider un champ, surtout pratique au doigt sur mobile.
   Ajouté automatiquement par enhanceClearableInputs() (voir JS), sauf
   sur les champs qui ont déjà leurs propres boutons dédiés (nom de
   fichier image, sélecteur de couleur). */
.input-clear-wrap{ position:relative; display:block; flex:1 1 auto; min-width:0; }
.input-clear-wrap input{ padding-right:34px; transition:border-color .2s ease, box-shadow .25s cubic-bezier(.2,.8,.2,1), transform .2s cubic-bezier(.2,.8,.2,1), background .2s ease, padding-left .2s ease; }
/* Liseré discret à gauche dès que le champ contient une valeur — repère
   visuel rapide sur un long formulaire pour voir d'un coup d'œil ce qui
   est déjà rempli, sans dépendre d'un attribut placeholder (peu fiable :
   la plupart des champs ici n'en ont pas). État calculé en JS (voir
   enhanceClearableInputs / sync ci-dessous), pas en CSS pur. */
.input-clear-wrap.has-value input{ border-left:3px solid rgba(171,143,102,.55); padding-left:11px; }
.input-clear-wrap.has-value input.invalid{ border-left-color:var(--danger); }
.input-clear-btn{
  position:absolute; top:50%; right:7px; z-index:2;
  width:22px; height:22px; border-radius:50%; border:none; padding:0;
  background:rgba(var(--ink-rgb),.09); color:var(--olive); font-size:.66rem;
  display:flex; align-items:center; justify-content:center; cursor:pointer;
  opacity:0; pointer-events:none; transform:translateY(-50%) scale(.6);
  transition:opacity .15s ease, transform .18s cubic-bezier(.34,1.56,.64,1), background .15s ease, color .15s ease;
}
.input-clear-wrap.has-value .input-clear-btn{ opacity:1; pointer-events:auto; transform:translateY(-50%) scale(1); }
.input-clear-btn:hover{ background:var(--danger); color:#fff; }
@media (prefers-reduced-motion: reduce){
  input, textarea, .input-clear-btn{ transition:none; }
}

/* Champ « nom de fichier + dossier forcé » */
.filepath-row{ display:flex; align-items:stretch; }
.filepath-prefix{
  display:flex; align-items:center; padding:0 12px; white-space:nowrap;
  background:var(--paper-soft); border:1.5px solid var(--line); border-right:none;
  border-radius:10px 0 0 10px; font-size:.85rem; color:var(--olive); font-family:var(--font-body);
}
.filepath-row input{ border-radius:0; }
.filepath-row:has(input.invalid) .filepath-prefix{ border-color:var(--danger); color:var(--danger); }
[data-img-field]{ display:flex; align-items:stretch; }
[data-img-field] .filepath-row{ flex:1; min-width:0; }
.filepath-browse-btn, .filepath-upload-btn{
  flex:none; display:flex; align-items:center; justify-content:center; width:42px;
  border:1.5px solid var(--line); border-left:none; border-radius:0;
  background:var(--paper-soft); color:var(--olive); cursor:pointer; font-size:1rem;
  transition:background .15s ease, color .15s ease, border-color .15s ease;
}
.filepath-browse-btn:hover{ background:var(--gold); color:#fff; border-color:var(--gold); }
.filepath-upload-btn:hover{ background:var(--ok); color:#fff; border-color:var(--ok); }
.filepath-upload-btn:last-child{ border-radius:0 10px 10px 0; }
.filepath-row:has(input.invalid) ~ .filepath-browse-btn,
.filepath-row:has(input.invalid) ~ .filepath-upload-btn{ border-color:var(--danger); }

/* ===================== MODALE « CHOISIR UNE IMAGE DU SERVEUR » ===================== */
.ip-tabs{ display:flex; gap:8px; margin-bottom:16px; }
.ip-tab{
  flex:1; padding:9px 10px; border-radius:11px; border:1.5px solid var(--line); background:#fffdf9;
  font-family:var(--font-body); font-weight:600; font-size:.82rem; color:var(--olive); cursor:pointer;
  transition:border-color .18s ease, color .18s ease, background .18s ease;
}
.ip-tab.is-active{ border-color:var(--gold); color:var(--ink); background:var(--paper-soft); }
.ip-search{ margin-bottom:14px; }
.ip-search input{ width:100%; }
.ip-grid{
  display:grid; grid-template-columns:repeat(auto-fill, minmax(112px, 1fr)); gap:12px;
}
.ip-item{
  border:1.5px solid var(--line); border-radius:12px; overflow:hidden; cursor:pointer; background:#fffdf9;
  transition:border-color .18s ease, transform .18s cubic-bezier(.34,1.56,.64,1), box-shadow .18s ease;
  text-align:left; padding:0; font-family:inherit;
}
.ip-item:hover{ border-color:var(--gold); transform:translateY(-2px); box-shadow:0 8px 18px rgba(var(--ink-rgb),.12); }
.ip-item.is-selected{ border-color:var(--gold); box-shadow:0 0 0 3px rgba(171,143,102,.22); }
.ip-item-thumb{
  width:100%; aspect-ratio:1/1; object-fit:cover; display:block; background:var(--paper-soft);
}
.ip-item-thumb.is-broken{ object-fit:contain; padding:14px; opacity:.75; }
.ip-item-name{
  font-size:.7rem; padding:6px 8px; color:var(--ink); word-break:break-word; line-height:1.3;
  border-top:1px solid var(--line);
}
.ip-item-bucket{
  display:inline-block; margin-top:2px; font-size:.62rem; font-weight:700; text-transform:uppercase;
  letter-spacing:.04em; color:var(--pine-dark); background:rgba(var(--pine-dark-rgb),.12);
  padding:1px 6px; border-radius:999px;
}
.ip-empty{ text-align:center; padding:36px 16px; color:var(--olive); font-size:.9rem; }
.ip-loading{ text-align:center; padding:36px 16px; color:var(--olive); font-size:.9rem; }
.ip-cross-note{
  display:flex; gap:8px; align-items:flex-start; margin-top:16px; padding:12px 14px; border-radius:12px;
  background:rgba(171,143,102,.14); color:#7a5f38; font-size:.84rem; line-height:1.5;
}

/* Étiquettes / tags — réordonnables (glisser-déposer + flèches) */
.tag-list{ display:flex; flex-wrap:wrap; gap:8px; margin-bottom:8px; }
.tag-chip{
  display:inline-flex; align-items:center; gap:2px; background:var(--paper-soft);
  border:1px solid var(--line); border-radius:999px; padding:6px 8px 6px 10px; font-size:.85rem;
  cursor:grab; transition:border-color .18s ease, box-shadow .18s ease, opacity .18s ease, transform .18s ease;
}
.tag-chip:active{ cursor:grabbing; }
.tag-chip:hover{ border-color:rgba(var(--pine-dark-rgb),.35); box-shadow:0 4px 12px rgba(var(--ink-rgb),.08); }
.tag-chip.is-dragging{ opacity:.35; }
.tag-chip.drag-over-before{ box-shadow:-3px 0 0 var(--gold); }
.tag-chip.drag-over-after{ box-shadow:3px 0 0 var(--gold); }
.tag-drag-handle{ color:#b8ac96; font-size:.9rem; line-height:1; padding:0 2px 0 0; cursor:grab; user-select:none; }
.tag-text{ padding:0 6px 0 2px; }
.tag-move{
  background:none; border:none; width:19px; height:19px; border-radius:50%; cursor:pointer;
  color:var(--olive); font-size:.72rem; line-height:1; display:inline-flex; align-items:center; justify-content:center;
  transition:background .15s ease, color .15s ease;
}
.tag-move:hover:not(:disabled){ background:var(--line); color:var(--ink); }
.tag-move:disabled{ opacity:.3; cursor:default; }
.tag-chip .tag-del{ background:var(--line); border:none; width:20px; height:20px; border-radius:50%; cursor:pointer; color:var(--ink); font-size:.8rem; line-height:1; margin-left:2px; }
.tag-chip .tag-del:hover{ background:var(--danger-light); color:var(--danger); }
.add-row{ display:flex; gap:10px; }
.add-row input{ flex:1; }

/* Cartes de services (cours & tarifs) */
.service-card{
  border:1px solid var(--line); border-radius:14px; padding:18px; margin-bottom:16px;
  background:#fffdf9; position:relative;
  transition:border-color .2s ease, box-shadow .2s ease, transform .2s ease;
}
.service-card:hover{ border-color:rgba(var(--pine-dark-rgb),.3); box-shadow:0 6px 20px rgba(var(--ink-rgb),.06); transform:translateY(-1px); }
.service-card-top{ display:flex; align-items:flex-start; justify-content:space-between; gap:10px 12px; margin-bottom:14px; flex-wrap:wrap; }
.service-card:not(.open) .service-card-top{ margin-bottom:0; }
.service-card-title{ font-family:var(--font-display); font-size:1.05rem; font-weight:600; cursor:pointer; flex:1 1 160px; min-width:0; }
.service-card-title .cat{ font-family:var(--font-body); font-size:.72rem; text-transform:uppercase; letter-spacing:.08em; color:var(--gold); display:block; font-weight:700; margin-bottom:2px; }
.service-card-subtitle{ display:block; font-family:var(--font-body); font-weight:400; font-size:.8rem; color:var(--olive); margin-top:2px; }
.service-chevron{ transition:transform .3s cubic-bezier(.4,0,.2,1); flex:none; color:var(--olive); }
.service-card.open .service-chevron{ transform:rotate(180deg); }
.service-card-body{
  padding-top:14px; display:none; overflow:hidden; max-height:0; opacity:0;
  transition:max-height .35s cubic-bezier(.4,0,.2,1), opacity .28s ease .04s;
}
.service-card.is-hidden{ opacity:.55; border-style:dashed; }
.service-hidden-badge{
  font-family:var(--font-body); font-size:.66rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em;
  color:var(--danger); background:var(--danger-light); padding:2px 8px; border-radius:999px; margin-left:8px;
  vertical-align:middle; display:none;
}
.service-card.is-hidden .service-hidden-badge{ display:inline-block; }
.service-visibility-toggle{ display:flex; align-items:center; gap:8px; margin-right:4px; }
.service-visibility-toggle span{ font-size:.72rem; font-weight:600; color:var(--olive); white-space:nowrap; }
.service-actions{ display:flex; align-items:center; gap:6px; flex:none; flex-wrap:wrap; justify-content:flex-end; }
/* Aperçus d'image : un ratio proche de l'usage réel sur le site (au lieu
   d'une hauteur fixe qui, étirée sur toute la largeur d'un grand écran,
   ne montrait plus qu'une mince bande de la photo). Largeur plafonnée
   pour rester lisible même dans un formulaire très large. */
.service-img-preview{
  display:block; width:100%; max-width:360px; aspect-ratio:16/9; height:auto;
  object-fit:cover; border-radius:10px; background:var(--paper-soft);
  margin-bottom:12px; border:1px dashed var(--line);
}
.service-img-preview.is-hero{ max-width:640px; aspect-ratio:21/9; }
.service-img-preview.is-wide{ max-width:640px; aspect-ratio:3/1; }
.service-img-preview.is-portrait{ max-width:220px; aspect-ratio:4/5; }
.service-img-preview.is-square{ max-width:220px; aspect-ratio:1/1; }
.service-img-preview.is-og{ max-width:480px; aspect-ratio:1.91/1; }
.img-fallback{
  display:flex; align-items:center; justify-content:center; text-align:center; font-size:.75rem; color:#a99f8a;
  padding:10px; width:100%; max-width:360px; aspect-ratio:16/9; border-radius:10px;
  background:var(--paper-soft); border:1px dashed var(--line); margin-bottom:12px;
}
.img-fallback.is-hero, .img-fallback.is-wide{ max-width:640px; aspect-ratio:21/9; }
.img-fallback.is-portrait, .img-fallback.is-square{ max-width:220px; aspect-ratio:4/5; }
.img-fallback.is-og{ max-width:480px; aspect-ratio:1.91/1; }
.reorder-hint{ font-size:.72rem; color:var(--olive); }
/* Sélecteurs de point focal (cadrage) d'un cours — un exemple concret par
   format d'affichage réel du site plutôt qu'un simple champ texte : la photo
   n'est pas cadrée pareil dans la vignette/fiche empilée sur mobile (bande
   large et basse) que dans la fiche à deux colonnes sur grand écran (bande
   étroite et haute, la photo occupant toute la colonne de gauche). Cliquer
   ou glisser dans l'aperçu déplace le point de cadrage ; la photo est
   recadrée en direct exactement comme sur le site (background-size:cover
   se comporte comme object-fit:cover).
   Compléments : grille de tiers + loupe de précision pendant le glisser,
   9 préréglages de cadrage rapide (coins/bords/centre), bouton pour
   copier le réglage vers l'autre format, petit flash doré de confirmation
   à chaque pose du point, et déplacement animé (flèches, préréglages,
   « Centrer ») — le glisser lui-même reste instantané, sans transition,
   pour ne jamais avoir l'impression de traîner derrière le doigt/curseur. */
.focal-pickers{ display:flex; flex-wrap:wrap; gap:20px; margin-bottom:16px; }
.focal-picker{ flex:1 1 220px; min-width:180px; max-width:280px; }
.focal-picker-label{ display:flex; align-items:center; gap:6px; font-size:.78rem; font-weight:600; color:var(--ink); margin-bottom:4px; }
.focal-picker-label .tag{
  font-size:.64rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em;
  color:var(--olive); background:var(--paper-soft); border:1px solid var(--line);
  border-radius:999px; padding:1px 8px;
}
.focal-picker-desc{ font-size:.72rem; color:var(--olive); margin-bottom:8px; }
.focal-picker-box{
  position:relative; width:100%; border-radius:10px; overflow:hidden;
  background:var(--paper-soft) no-repeat center/cover; border:1px solid var(--line);
  cursor:crosshair; touch-action:none; user-select:none;
  transition:border-color .15s ease, box-shadow .15s ease;
}
.focal-picker-box.is-mobile{ aspect-ratio:8/5; transition:aspect-ratio .3s cubic-bezier(.4,0,.2,1), border-color .15s ease, box-shadow .15s ease; }
.focal-picker-box.is-desktop{ aspect-ratio:31/60; }
/* Repositionnement « quantisé » (flèches clavier, préréglages, reset) :
   transition douce activée seulement pour ces cas via .is-animated —
   jamais pendant un glisser réel (retirée dès pointerdown), pour rester
   parfaitement réactif au doigt/curseur. */
.focal-picker-box.is-animated{ transition:border-color .15s ease, box-shadow .15s ease, background-position .25s cubic-bezier(.4,0,.2,1); }
.focal-picker-box.is-animated .focal-picker-dot{ transition:left .25s cubic-bezier(.4,0,.2,1), top .25s cubic-bezier(.4,0,.2,1), box-shadow .15s ease; }
.focal-picker-box:focus-visible{ outline:2px solid var(--gold); outline-offset:2px; }
.focal-picker-box.is-dragging{ cursor:grabbing; }
/* Grille de tiers — repère de composition classique, visible seulement
   au survol/focus/glisser pour ne pas alourdir l'aperçu au repos. */
.focal-picker-grid{ position:absolute; inset:0; pointer-events:none; opacity:0; transition:opacity .18s ease; z-index:1; }
.focal-picker-grid span{ position:absolute; background:rgba(255,255,255,.55); box-shadow:0 0 0 1px rgba(0,0,0,.12); }
.focal-picker-grid span:nth-child(1){ left:33.333%; top:0; bottom:0; width:1px; }
.focal-picker-grid span:nth-child(2){ left:66.666%; top:0; bottom:0; width:1px; }
.focal-picker-grid span:nth-child(3){ top:33.333%; left:0; right:0; height:1px; }
.focal-picker-grid span:nth-child(4){ top:66.666%; left:0; right:0; height:1px; }
.focal-picker-box:hover .focal-picker-grid,
.focal-picker-box:focus-visible .focal-picker-grid,
.focal-picker-box.is-dragging .focal-picker-grid{ opacity:1; }
/* Loupe de précision : zoom de la photo ENTIÈRE (pas de l'aperçu recadré)
   centré sur le point visé, affichée juste au-dessus du doigt/curseur
   pendant le glisser — utile pour caler précisément un petit détail
   (regard, étiquette…) qu'un point sur un aperçu déjà recadré rendrait
   difficile à distinguer. */
.focal-picker-loupe{
  position:absolute; width:64px; height:64px; border-radius:50%; border:3px solid #fff;
  box-shadow:0 3px 10px rgba(0,0,0,.35), 0 0 0 1.5px rgba(0,0,0,.15);
  pointer-events:none; opacity:0; z-index:5; background-repeat:no-repeat;
  transform:translate(-50%,calc(-100% - 16px)) scale(.85);
  transition:opacity .12s ease, transform .12s ease;
}
.focal-picker-box.is-dragging .focal-picker-loupe{ opacity:1; transform:translate(-50%,calc(-100% - 16px)) scale(1); }
.focal-picker-hint{
  display:flex; align-items:center; gap:6px; font-size:.7rem; color:var(--olive);
  margin-top:7px; padding:6px 9px; border-radius:8px; background:var(--paper-soft);
  border:1px solid var(--line); line-height:1.4;
}
.focal-picker-hint .dot{ flex:none; width:7px; height:7px; border-radius:50%; background:#8fbf7a; }
.focal-picker-hint.is-cropped .dot{ background:#e0ac4c; }
.focal-picker-hint.is-empty{ display:none; }
.focal-picker-box.is-empty{ cursor:default; }
.focal-picker-box.is-empty::before{
  content:"Ajoute une image ci-dessus pour régler le cadrage"; position:absolute; inset:0;
  display:flex; align-items:center; justify-content:center; text-align:center;
  font-size:.72rem; color:#a99f8a; padding:10px; z-index:2;
}
.focal-picker-dot{
  position:absolute; z-index:2; width:20px; height:20px; border-radius:50%;
  background:rgba(var(--gold-rgb,207,159,79),.92); border:2.5px solid #fff;
  box-shadow:0 1px 4px rgba(0,0,0,.4); transform:translate(-50%,-50%);
  pointer-events:none;
}
.focal-picker-box:active .focal-picker-dot, .focal-picker-box.is-dragging .focal-picker-dot{ box-shadow:0 0 0 5px rgba(var(--gold-rgb,207,159,79),.28); }
/* Flash doré de confirmation à chaque pose du point (clic, glisser
   relâché, préréglage, flèche clavier, reset) — se relance à chaque
   déclenchement même répété (voir pulseDot() en JS). */
@keyframes focalDotPulse{
  0%{ box-shadow:0 0 0 0 rgba(var(--gold-rgb,207,159,79),.55), 0 1px 4px rgba(0,0,0,.4); }
  70%{ box-shadow:0 0 0 13px rgba(var(--gold-rgb,207,159,79),0), 0 1px 4px rgba(0,0,0,.4); }
  100%{ box-shadow:0 0 0 13px rgba(var(--gold-rgb,207,159,79),0), 0 1px 4px rgba(0,0,0,.4); }
}
.focal-picker-dot.is-pulsing{ animation:focalDotPulse .55s ease-out; }
/* Préréglages rapides — grille 3×3 façon « ancre de recadrage » (coins,
   bords, centre) : chaque bouton représente sa propre position dans la
   grille, un clic pose directement le point là, sans viser à la souris
   pour les cas les plus courants. */
.focal-picker-presets{ display:grid; grid-template-columns:repeat(3,18px); grid-template-rows:repeat(3,18px); gap:3px; margin:9px 0 2px; }
.focal-preset-btn{
  padding:0; border-radius:4px; border:1px solid var(--line); background:var(--paper-soft);
  cursor:pointer; transition:background .12s ease, border-color .12s ease, transform .1s ease;
}
.focal-preset-btn:hover{ background:var(--gold-light); border-color:var(--gold); transform:scale(1.12); }
.focal-preset-btn:active{ transform:scale(.9); }
.focal-preset-btn:focus-visible{ outline:2px solid var(--gold); outline-offset:1px; }
.focal-preset-btn.is-active{ background:var(--gold); border-color:var(--gold); box-shadow:0 0 0 2px rgba(var(--gold-rgb,207,159,79),.25); }
.focal-picker-box.is-empty ~ .focal-picker-presets .focal-preset-btn{ cursor:default; opacity:.45; pointer-events:none; }
.focal-picker-foot{ display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:6px; }
.focal-picker-coords{ font-size:.68rem; color:var(--olive); font-variant-numeric:tabular-nums; }
.focal-picker-actions{ display:flex; align-items:center; gap:11px; }
.focal-picker-sync, .focal-picker-reset{
  font-size:.68rem; font-weight:600; color:var(--pine-dark); background:none; border:none;
  padding:2px 4px; cursor:pointer; text-decoration:underline; text-underline-offset:2px;
}
.focal-picker-sync:hover, .focal-picker-reset:hover{ color:var(--gold); }
.focal-picker-sync:disabled{ opacity:.4; cursor:default; text-decoration:none; }
@media (max-width:640px){ .focal-pickers{ gap:16px; } .focal-picker{ max-width:none; flex:1 1 100%; } }
/* Cibles tactiles agrandies sur écran tactile (doigt moins précis qu'un
   curseur de souris) : point de cadrage et préréglages plus grands. */
@media (pointer:coarse){
  .focal-picker-dot{ width:26px; height:26px; }
  .focal-picker-presets{ grid-template-columns:repeat(3,24px); grid-template-rows:repeat(3,24px); }
  .focal-picker-loupe{ width:84px; height:84px; }
}
/* Les 3 images principales (héros, contact, portrait) sont déjà limitées
   en largeur (max-width sur l'aperçu) — sur grand écran elles peuvent
   donc se placer côte à côte plutôt que de s'empiler en occupant toute
   la largeur pour rien. flex-wrap fait naturellement 1 par ligne dès que
   l'espace manque (mobile/tablette), sans media query dédiée. */
.main-images-grid{ display:flex; flex-wrap:wrap; gap:26px 32px; }
.main-images-grid > .field{ flex:1 1 300px; min-width:260px; margin-bottom:0; }

@media (max-width:640px){
  .service-card{ padding:14px; }
  .service-actions{ flex:1 1 100%; justify-content:flex-start; gap:5px; }
  .service-actions .btn-icon{ min-width:32px; min-height:32px; padding:6px; }
  .service-actions .switch{ margin-right:2px; }
  .service-actions .switch-track{ width:40px; height:24px; }
  .service-actions .switch-thumb{ width:18px; height:18px; }
}

.add-service-btn{ width:100%; justify-content:center; border:2px dashed var(--line); background:transparent; color:var(--olive); padding:16px; }
.add-service-btn:hover{ border-color:var(--gold); color:var(--gold); background:var(--paper-soft); }

/* Barre d'action fixe */
.actionbar{
  position:fixed; left:0; right:0; bottom:0; z-index:60;
  background:rgba(var(--paper-rgb),.98); backdrop-filter:blur(8px);
  border-top:1px solid var(--line);
  padding:10px 0; padding-bottom:calc(10px + env(safe-area-inset-bottom));
}
.actionbar .container{ display:flex; flex-direction:column; gap:8px; }
.actionbar-row-top{ display:flex; gap:8px; align-items:center; }
.actionbar-download{ width:100%; }
.file-input-label{ display:inline-flex; }
.file-input-label input[type=file]{ display:none; }
/* Écrans larges : tout tient sur une seule ligne — inutile d'empiler
   les gros boutons pleine largeur comme sur mobile. Les icônes restent
   à gauche, « Télécharger » et « Publier » (l'action principale, mise
   en avant à l'extrémité droite) suivent en largeur auto. */
@media(min-width:720px){
  .actionbar .container{ flex-direction:row; align-items:center; gap:14px; }
  .actionbar-row-top{ flex:none; order:0; }
  .actionbar-download{ width:auto; flex:none; white-space:nowrap; }
  #downloadBtn{ order:1; margin-left:auto; }
  #publishBtn{ order:2; }
}

.toast{
  position:fixed; top:14px; left:50%; transform:translateX(-50%) translateY(-140%) scale(.94);
  background:var(--ink); color:var(--cream); padding:12px 20px; border-radius:12px;
  font-size:.88rem; font-weight:600; z-index:200; box-shadow:0 10px 26px rgba(0,0,0,.25);
  transition:transform .32s cubic-bezier(.34,1.56,.64,1), opacity .2s ease;
  opacity:0; max-width:min(420px, calc(100vw - 32px)); white-space:pre-line; text-align:left; line-height:1.5;
}
.toast.show{ transform:translateX(-50%) translateY(0) scale(1); opacity:1; }
.toast.error{ background:var(--danger); }

/* Toast riche pour Annuler / Rétablir */
.toast.action-toast{
  padding:13px 16px 11px; text-align:left; border-left:4px solid var(--gold);
  background:linear-gradient(165deg, #46392a 0%, #2f2619 100%);
}
.toast.action-toast.is-undo{ border-left-color:#e0ac4c; }
.toast.action-toast.is-redo{ border-left-color:#8fbf7a; }
.toast-row{ display:flex; align-items:center; gap:12px; }
.toast-icon{
  flex:none; width:32px; height:32px; border-radius:50%;
  display:flex; align-items:center; justify-content:center;
  animation:toastIconPop .38s cubic-bezier(.34,1.56,.64,1);
}
.toast-icon.undo{ background:rgba(224,172,76,.22); color:#f2d391; }
.toast-icon.redo{ background:rgba(143,191,122,.22); color:#c3e6b0; }
@keyframes toastIconPop{ from{ transform:scale(.3) rotate(-25deg); opacity:0; } to{ transform:scale(1) rotate(0); opacity:1; } }
.toast-copy{ min-width:0; }
.toast-title{ font-family:var(--font-display); font-weight:600; font-size:.98rem; letter-spacing:.01em; }
.toast-desc{
  font-family:var(--font-body); font-weight:500; font-size:.78rem; color:rgba(var(--cream-rgb),.72);
  margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:250px;
}
.toast-progress{ height:3px; border-radius:3px; background:rgba(var(--cream-rgb),.16); margin-top:11px; overflow:hidden; }
.toast-progress span{ display:block; height:100%; width:100%; transform-origin:left; }
.is-undo .toast-progress span{ background:linear-gradient(90deg,#f2d391,#c98a2c); }
.is-redo .toast-progress span{ background:linear-gradient(90deg,#c3e6b0,#5f7a4f); }
.toast-progress span.run{ animation:toastShrink 3.1s linear forwards; }
@keyframes toastShrink{ from{ transform:scaleX(1); } to{ transform:scaleX(0); } }

.empty-state{
  text-align:center; padding:60px 20px; color:var(--olive);
}
.empty-state h2{ color:var(--ink); }
.empty-state .btn{ margin-top:16px; }

/* ---------------------------------------------------------------------
   Robustesse — indicateurs de sauvegarde & récupération d'erreurs
   --------------------------------------------------------------------- */
.status-pill.warn{
  background:var(--danger-light); color:var(--danger); border-color:#e7bdae;
  cursor:help;
}
.status-pill.warn::before{ content:"⚠"; }

.empty-state-error{
  text-align:left; max-width:520px; margin:0 auto 24px;
  background:var(--danger-light); border:1px solid #e7bdae; border-radius:var(--radius);
  padding:18px 20px; color:#7a3626;
}
.empty-state-error strong{ display:block; margin-bottom:6px; font-family:var(--font-display); font-size:1.02rem; color:#6b2f21; }
.empty-state-error p{ margin:0; font-size:.88rem; line-height:1.55; }

.error-banner{
  position:fixed; left:16px; right:16px; z-index:950;
  bottom:calc(16px + env(safe-area-inset-bottom));
  max-width:560px; margin:0 auto;
  background:#3d2c22; color:#f6e6dc;
  border-radius:var(--radius); padding:14px 16px;
  display:flex; align-items:flex-start; gap:12px;
  box-shadow:0 20px 50px rgba(0,0,0,.35);
  transform:translateY(140%); opacity:0;
  transition:transform .38s cubic-bezier(.34,1.56,.64,1), opacity .3s ease;
}
.error-banner.show{ transform:translateY(0); opacity:1; }
.error-banner-icon{ font-size:1.2rem; line-height:1; flex:none; margin-top:1px; }
.error-banner-text{ flex:1; font-size:.85rem; line-height:1.5; }
.error-banner-close{
  flex:none; background:none; border:none; color:#f6e6dc; opacity:.75;
  font-size:1rem; cursor:pointer; padding:2px 4px; line-height:1;
}
.error-banner-close:hover{ opacity:1; }

.loading-overlay{
  position:fixed; inset:0; background:var(--paper); z-index:300;
  display:flex; align-items:center; justify-content:center; flex-direction:column; gap:14px;
  transition:opacity .35s ease;
}
.loading-overlay.is-hiding{ opacity:0; }
.spinner{
  width:34px; height:34px; border-radius:50%; border:3px solid var(--line); border-top-color:var(--gold);
  animation:spin .8s linear infinite;
}
@keyframes spin{ to{ transform:rotate(360deg); } }
/* Loader thématique de l'écran de chargement initial — trois pattes qui
   rebondissent en cascade plutôt qu'un rond générique, pour rester dans
   l'esprit du site dès la première seconde. */
.paw-loader{ display:flex; gap:10px; }
.paw-loader span{
  font-size:1.4rem; display:inline-block; opacity:.4; line-height:1;
  animation:pawTrail 1.1s ease-in-out infinite;
  filter:drop-shadow(0 3px 6px rgba(var(--ink-rgb),.15));
}
.paw-loader span:nth-child(2){ animation-delay:.15s; }
.paw-loader span:nth-child(3){ animation-delay:.3s; }
@keyframes pawTrail{
  0%,60%,100%{ transform:translateY(0) scale(.85); opacity:.4; }
  30%{ transform:translateY(-10px) scale(1.15); opacity:1; }
}

/* ---------------------------------------------------------------------
   Petite fête de pattes de chien au moment où une publication réussit
   — purement décoratif, ne bloque aucune interaction (pointer-events:none)
   et se retire tout seul après son animation.
   --------------------------------------------------------------------- */
.pub-burst{ position:fixed; inset:0; z-index:1200; pointer-events:none; display:flex; align-items:center; justify-content:center; }
.pub-burst-glow{
  position:absolute; width:60vmin; height:60vmin; border-radius:50%;
  background:radial-gradient(circle, rgba(171,143,102,.35), transparent 70%);
  animation:pubGlowFlash .9s cubic-bezier(.2,.8,.2,1) forwards;
}
@keyframes pubGlowFlash{ 0%{ transform:scale(.3); opacity:0; } 35%{ opacity:1; } 100%{ transform:scale(1.3); opacity:0; } }
.pub-burst-center{ font-size:2.6rem; animation:pubCenterPop .55s cubic-bezier(.34,1.56,.64,1); filter:drop-shadow(0 6px 14px rgba(var(--ink-rgb),.25)); }
@keyframes pubCenterPop{ 0%{ transform:scale(.3); opacity:0; } 60%{ transform:scale(1.18); opacity:1; } 100%{ transform:scale(1); opacity:1; } }
.pub-burst-paw{ position:absolute; font-size:1.5rem; opacity:0; animation:pubPawBurst .9s cubic-bezier(.16,.8,.3,1) forwards; }
@keyframes pubPawBurst{
  0%{ transform:translate(0,0) scale(.4) rotate(0deg); opacity:0; }
  18%{ opacity:1; }
  100%{ transform:translate(var(--tx),var(--ty)) scale(1) rotate(var(--tr)); opacity:0; }
}
/* Rubans de confettis qui tombent depuis le haut de l'écran — surcouche
   festive au-dessus du bouquet de pattes, positions/couleurs/délais
   tirés aléatoirement en JS via variables CSS. */
.pub-confetti{
  position:absolute; top:-5vh; width:9px; height:16px; border-radius:2px;
  opacity:0; animation:pubConfettiFall 1.5s cubic-bezier(.3,0,.4,1) forwards;
}
@keyframes pubConfettiFall{
  0%{ transform:translate(0,0) rotate(0deg); opacity:0; }
  8%{ opacity:1; }
  100%{ transform:translate(var(--cx),105vh) rotate(var(--cr)); opacity:.9; }
}
.spinner-sm{
  width:18px; height:18px; border-radius:50%; border:2.5px solid rgba(var(--ink-rgb),.15); border-top-color:var(--gold);
  animation:spin .7s linear infinite; flex:none;
}
.pm-slot.is-uploading{ border-color:var(--gold); background:rgba(171,143,102,.08); }
.pm-slot.is-uploading .pm-slot-status{ background:transparent; }
.tag-chip{ transition:transform .18s cubic-bezier(.34,1.56,.64,1), box-shadow .18s ease; }
.tag-chip:hover{ transform:translateY(-1px); box-shadow:0 4px 12px rgba(var(--ink-rgb),.08); }

.color-row{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.color-row .color-swatch{
  width:44px; height:44px; padding:0; border:1.5px solid var(--line); border-radius:10px; background:none; cursor:pointer;
  transition:transform .18s cubic-bezier(.34,1.56,.64,1), box-shadow .18s ease;
  position:relative; flex:none;
}
.color-row .color-swatch:hover{ transform:scale(1.06); box-shadow:0 4px 14px rgba(var(--ink-rgb),.14); }
.color-row .color-swatch:focus-visible{ outline:2px solid var(--gold); outline-offset:2px; }
.color-row .color-swatch.is-open{ box-shadow:0 0 0 3px rgba(var(--ink-rgb),.12); }
.color-row input[type=text]{ flex:1; min-width:120px; }
.color-row .field-error{ width:100%; margin-top:8px; }
.color-row .theme-field-restore{ flex:none; }

/* ---------------------------------------------------------------------
   Sélecteur de couleur précis (popover) — remplace le picker natif du
   navigateur par une vraie palette saturation/luminosité + teinte,
   utilisable au doigt comme à la souris, en plus des couleurs rapides
   et des préréglages de palette complets.
   --------------------------------------------------------------------- */
.color-picker-popover{
  position:fixed; z-index:520; width:236px; max-width:calc(100vw - 24px);
  background:#fffdf9; border:1px solid var(--line); border-radius:16px;
  box-shadow:0 18px 44px rgba(var(--ink-rgb),.22); padding:16px;
  display:none; flex-direction:column; gap:12px;
}
.color-picker-popover.is-open{ display:flex; }
.cp-sv{
  position:relative; width:100%; height:140px; border-radius:10px; overflow:hidden;
  cursor:crosshair; touch-action:none; border:1px solid var(--line);
}
.cp-sv-white{ position:absolute; inset:0; background:linear-gradient(to right, #fff, rgba(255,255,255,0)); }
.cp-sv-black{ position:absolute; inset:0; background:linear-gradient(to top, #000, rgba(0,0,0,0)); }
.cp-sv-thumb{
  position:absolute; width:16px; height:16px; border-radius:50%; border:2.5px solid #fff;
  box-shadow:0 0 0 1.5px rgba(0,0,0,.4), 0 2px 6px rgba(0,0,0,.35);
  transform:translate(-50%,-50%); pointer-events:none;
}
.cp-hue-row{ display:flex; align-items:center; }
.cp-hue-slider{
  -webkit-appearance:none; appearance:none; width:100%; height:14px; border-radius:999px; cursor:pointer;
  background:linear-gradient(to right, #f00 0%, #ff0 17%, #0f0 33%, #0ff 50%, #00f 67%, #f0f 83%, #f00 100%);
  touch-action:pan-y;
}
.cp-hue-slider::-webkit-slider-thumb{
  -webkit-appearance:none; width:22px; height:22px; border-radius:50%; background:#fff;
  border:2.5px solid var(--ink); box-shadow:0 2px 6px rgba(0,0,0,.3); cursor:pointer; margin-top:-4px;
}
.cp-hue-slider::-moz-range-thumb{
  width:22px; height:22px; border-radius:50%; background:#fff; border:2.5px solid var(--ink);
  box-shadow:0 2px 6px rgba(0,0,0,.3); cursor:pointer;
}
.cp-hue-slider::-moz-range-track{ height:14px; border-radius:999px; }
.cp-hex-row{ display:flex; align-items:center; gap:8px; }
.cp-hex-prefix{ font-family:var(--font-body); font-weight:700; color:var(--olive); flex:none; }
.cp-hex-input{ flex:1; min-width:0; text-transform:uppercase; }
.cp-current-swatch{ width:32px; height:32px; border-radius:8px; border:1.5px solid var(--line); flex:none; }
.cp-quick-label{ font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--olive); }
.cp-quick-grid{ display:grid; grid-template-columns:repeat(8, 1fr); gap:6px; }
.cp-quick-swatch{
  width:100%; aspect-ratio:1; border-radius:6px; border:1.5px solid rgba(var(--ink-rgb),.12); cursor:pointer; padding:0;
  transition:transform .15s cubic-bezier(.34,1.56,.64,1);
}
.cp-quick-swatch:hover{ transform:scale(1.12); }

/* ---------------------------------------------------------------------
   Thème / couleurs — panneau repensé : intro, aperçu ciblé, préréglages,
   champs groupés par usage.
   --------------------------------------------------------------------- */
.theme-intro{ margin-bottom:18px; }
.theme-intro .desc{ margin:0; }

/* Préréglages de palette — groupés par ambiance, avec un aperçu en
   dégradé (plus parlant que de simples pastilles) et une mise en
   avant nette du préréglage actuellement appliqué. */
.theme-preset-group{ margin-bottom:16px; }
.theme-preset-group:last-child{ margin-bottom:0; }
.theme-preset-group-title{
  font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em;
  color:var(--gold); margin-bottom:9px;
}
.theme-presets{ display:flex; flex-wrap:wrap; gap:10px; }
.theme-preset{
  display:flex; align-items:center; gap:10px; padding:7px 14px 7px 7px;
  border:1.5px solid var(--line); border-radius:999px; background:#fffdf9; cursor:pointer;
  font-family:var(--font-body); font-size:.8rem; font-weight:600; color:var(--ink);
  transition:border-color .18s ease, transform .18s cubic-bezier(.34,1.56,.64,1), box-shadow .18s ease;
  position:relative;
}
.theme-preset:hover{ border-color:var(--gold); transform:translateY(-1px) scale(1.015); box-shadow:0 8px 20px rgba(var(--ink-rgb),.1); }
.theme-preset:active{ transform:scale(.97); }
.theme-preset.is-current{ border-color:var(--gold); background:var(--paper-soft); box-shadow:0 0 0 3px rgba(171,143,102,.16); }
.theme-preset.is-current::after{
  content:"✓"; position:absolute; top:-6px; right:-6px; width:18px; height:18px; border-radius:50%;
  background:var(--gold); color:#fff; font-size:.62rem; font-weight:800; display:flex; align-items:center; justify-content:center;
  box-shadow:0 2px 5px rgba(var(--ink-rgb),.25);
}
.theme-preset-swatch{
  display:flex; width:34px; height:22px; border-radius:7px; overflow:hidden; flex:none;
  border:1px solid rgba(var(--ink-rgb),.12); box-shadow:0 1px 3px rgba(var(--ink-rgb),.12);
}
.theme-preset-swatch span{ flex:1; height:100%; }

/* Aperçu ciblé — mini maquette du site qui suit les vraies variables CSS */
.theme-preview-card{
  border:1px solid var(--line); border-radius:14px; overflow:hidden; margin-bottom:22px;
  box-shadow:0 10px 30px rgba(var(--ink-rgb),.08);
}
.tp-dots{ display:flex; gap:6px; padding:9px 12px; background:#e9e3d3; }
.tp-dots span{ width:9px; height:9px; border-radius:50%; background:#c9bfa4; }
.tp-header{
  display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;
  padding:12px 18px; background:var(--paper); border-bottom:1px solid var(--line); transition:background .25s ease;
}
.tp-brand{ font-family:var(--font-display); font-weight:700; font-size:1rem; color:var(--ink); transition:color .25s ease; }
.tp-brand em{ font-style:normal; color:var(--gold); transition:color .25s ease; }
.tp-nav{ font-size:.72rem; color:var(--olive); letter-spacing:.03em; transition:color .25s ease; }
.tp-hero{
  padding:26px 20px; background:var(--pine-dark); transition:background .25s ease; position:relative;
}
.tp-eyebrow{
  font-size:.66rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
  color:var(--gold-light); margin-bottom:6px; transition:color .25s ease;
}
.tp-title{
  font-family:var(--font-display); font-size:1.15rem; font-weight:600; color:var(--cream);
  margin-bottom:14px; max-width:280px; transition:color .25s ease;
}
.tp-title em{ font-style:italic; color:var(--gold); transition:color .25s ease; }
.tp-btn{
  display:inline-flex; align-items:center; font-family:var(--font-body); font-weight:600; font-size:.8rem;
  padding:9px 16px; border-radius:999px; border:none; cursor:default;
  background:linear-gradient(135deg,var(--gold-light) 0%,var(--gold) 100%); color:#3d2c0f;
  transition:background .25s ease;
}
.tp-section{ padding:18px; background:var(--paper); display:flex; transition:background .25s ease; }
.tp-card{
  background:var(--paper-soft); border-radius:10px; padding:14px; max-width:230px;
  transition:background .25s ease;
}
.tp-dot{ display:block; width:9px; height:9px; border-radius:50%; background:var(--pine); margin-bottom:9px; transition:background .25s ease; }
.tp-card-title{ font-family:var(--font-display); font-weight:600; font-size:.9rem; color:var(--ink); margin-bottom:4px; transition:color .25s ease; }
.tp-card-text{ font-size:.76rem; color:var(--olive); line-height:1.5; margin-bottom:10px; transition:color .25s ease; }
.tp-pill{
  display:inline-block; font-size:.68rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase;
  padding:4px 10px; border-radius:999px; background:rgba(var(--pine-dark-rgb),.14); color:var(--pine);
  transition:color .25s ease, box-shadow .25s ease;
}
/* Section en alternance : même fond que les cartes (paperSoft), mais
   appliqué à toute une section — pour bien montrer que ce n'est pas
   qu'un fond de carte, contrairement à ce que la maquette précédente
   laissait penser. */
.tp-alt-section{ padding:16px 18px; background:var(--paper-soft); transition:background .25s ease; }
.tp-alt-title{ font-family:var(--font-display); font-weight:600; font-size:.85rem; color:var(--ink); margin-bottom:3px; transition:color .25s ease; }
.tp-alt-text{ font-size:.72rem; line-height:1.5; color:var(--olive); transition:color .25s ease; }
/* Pied de page miniature : seconde utilisation concrète du fond foncé
   et du texte clair (en plus du héros), comme sur le vrai site. */
.tp-footer{
  display:flex; align-items:center; justify-content:space-between; gap:10px;
  padding:12px 18px; font-size:.72rem; font-weight:600; background:var(--pine-dark); color:var(--cream);
  transition:background .25s ease, color .25s ease;
}
.tp-footer-link{ color:var(--gold); text-decoration:underline; text-underline-offset:2px; transition:color .25s ease; }
/* Surbrillance ciblée : la zone concernée porte un contour net + un
   petit badge qui nomme le champ, et tout le reste de la maquette
   s'assombrit légèrement — pour qu'il n'y ait plus aucune ambiguïté
   sur ce qui va changer (et où d'autre la même couleur est utilisée).
   :has() permet de ne dimmer que les zones réellement étrangères à la
   sélection, sans jamais assombrir un parent ou un enfant de la zone
   mise en avant (ce qui la ferait paraître grisée malgré elle). */
[data-role]{ position:relative; }
.theme-preview-card:has(.tp-focus) [data-role]{
  opacity:.4; filter:saturate(.55); transition:opacity .22s ease, filter .22s ease;
}
.theme-preview-card:has(.tp-focus) [data-role].tp-focus,
.theme-preview-card:has(.tp-focus) [data-role].tp-focus [data-role],
.theme-preview-card:has(.tp-focus) [data-role]:has(.tp-focus){
  opacity:1; filter:none;
}
[data-role].tp-focus::after{
  content:""; position:absolute; inset:-4px; border-radius:8px; z-index:3;
  outline:2.5px solid var(--gold); outline-offset:1px; pointer-events:none;
  box-shadow:0 0 0 4px rgba(255,255,255,.55), 0 0 16px 3px rgba(var(--ink-rgb),.3);
  animation:tpPulse 1.1s ease-in-out infinite;
}
[data-role].tp-focus::before{
  content:attr(data-role-label); position:absolute; top:-11px; left:4px; z-index:4;
  background:var(--gold); color:#3d2c0f; font-size:.62rem; font-weight:800; letter-spacing:.01em;
  padding:2px 8px; border-radius:999px; white-space:nowrap; pointer-events:none;
  box-shadow:0 2px 8px rgba(0,0,0,.28); animation:tpLabelIn .18s ease both;
}
@keyframes tpLabelIn{ from{ opacity:0; transform:translateY(3px); } to{ opacity:1; transform:translateY(0); } }
@keyframes tpPulse{ 0%,100%{ filter:brightness(1); } 50%{ filter:brightness(1.1); } }
.theme-preview-hint{ font-size:.76rem; color:var(--olive); text-align:center; padding:8px 12px; background:var(--paper-soft); }

/* Groupes de champs de couleur — repliables individuellement, pour
   garder la maquette d'aperçu visible sans avoir à scroller sur un
   écran peu haut (portable, fenêtre non maximisée…). Replié par défaut
   uniquement sur petit écran ; ouvert par défaut sur desktop où la
   place ne manque généralement pas. */
.theme-group{ margin-bottom:14px; border:1px solid transparent; }
.theme-group:last-of-type{ margin-bottom:0; }
.theme-group-head{
  display:flex; align-items:center; justify-content:space-between; gap:10px;
  cursor:pointer; user-select:none; padding:8px 4px; margin:0 -4px 0; border-radius:10px;
  transition:background .2s ease;
}
.theme-group-head:hover{ background:rgba(var(--ink-rgb),.04); }
.theme-group-head-text{ min-width:0; }
.theme-group-title{
  font-family:var(--font-display); font-size:.98rem; font-weight:600; color:var(--ink); margin-bottom:2px;
}
.theme-group-desc{ font-size:.78rem; color:var(--olive); }
.theme-group-chevron{ transition:transform .3s cubic-bezier(.4,0,.2,1); flex:none; color:var(--olive); }
.theme-group.open .theme-group-chevron{ transform:rotate(180deg); }
.theme-group-body{
  padding-top:12px; display:none; overflow:hidden; max-height:0; opacity:0;
  transition:max-height .35s cubic-bezier(.4,0,.2,1), opacity .28s ease .04s;
}
/* Sur grand écran (PC, 2K+), les cartes de couleur d'un même groupe
   passent en colonnes plutôt que de s'empiler une par une — ça reste
   1 seule colonne par défaut (mobile/tablette) pour ne rien changer là
   où l'espace horizontal manque. */
.theme-fields-grid{ display:grid; grid-template-columns:1fr; gap:12px; }
@media (min-width:900px){ .theme-fields-grid{ grid-template-columns:1fr 1fr; } }
@media (min-width:1440px){ .theme-fields-grid{ grid-template-columns:1fr 1fr 1fr; } }
.theme-field-card{
  border:1px solid var(--line); border-radius:12px; padding:14px; margin-bottom:12px;
  background:#fffdf9; transition:border-color .2s ease, box-shadow .2s ease, background .2s ease;
}
.theme-fields-grid .theme-field-card{ margin-bottom:0; }
.theme-field-card:last-child{ margin-bottom:0; }
.theme-field-card:hover, .theme-field-card.is-active{
  border-color:var(--gold); box-shadow:0 6px 18px rgba(var(--ink-rgb),.07); background:#fff;
}
.theme-field-card label{ display:block; font-size:.85rem; font-weight:600; color:var(--ink); margin-bottom:2px; }
.theme-usage{ font-size:.76rem; color:var(--olive); margin-bottom:10px; line-height:1.45; }
.theme-usage strong{ color:var(--ink); font-weight:600; }
/* Bouton tactile qui déclenche la surbrillance dans l'aperçu — la souris
   a le survol, mais sur téléphone il faut un geste explicite (tap) pour
   activer la même mise en avant. Caché par défaut, réservé au tactile
   via la media query ci-dessous (les souris/trackpads gardent le survol
   comme avant, pour garder l'aperçu "actif" en continu en travaillant). */
.theme-field-touch-toggle{ display:none; flex:none; }
@media (hover:none), (pointer:coarse){
  .theme-field-touch-toggle{ display:inline-flex; }
}
.theme-field-touch-toggle.is-active{ background:var(--gold); border-color:var(--gold); color:#3d2c0f; }

/* ---------------------------------------------------------------------
   Aperçus "comme sur le site" — Héros, Présentation, Section Pourquoi.
   Reprennent exactement les polices (Fraunces/Work Sans) et les couleurs
   réelles du site (déjà partagées via les mêmes variables CSS), pour se
   projeter sans avoir à publier ni ouvrir "Aperçu du site". Mis à jour en
   direct à chaque frappe (voir updateHeroPreview/updatePresentationPreview
   /updateWhyPreview en JS) — jamais un design figé, toujours le contenu
   réellement tapé.
   --------------------------------------------------------------------- */
.field-preview-wrap{ margin:20px 0 4px; }
.field-preview-label{ font-size:.78rem; font-weight:600; color:var(--ink); margin-bottom:8px; }

.hero-mini-preview{
  background:linear-gradient(160deg, var(--pine-dark) 0%, #2d2115 100%);
  border-radius:16px; padding:30px 24px; text-align:center;
  display:flex; flex-direction:column; align-items:center; gap:11px;
  box-shadow:0 10px 26px rgba(var(--ink-rgb),.18);
}
.hmp-badge{
  font-family:var(--font-body); font-size:.68rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
  color:var(--gold-light); background:rgba(255,255,255,.08); padding:5px 14px; border-radius:999px;
}
.hmp-title{
  font-family:var(--font-display); font-weight:600; font-size:1.45rem; line-height:1.25; color:var(--cream);
  max-width:26ch; margin:0;
}
.hmp-title em{ color:var(--gold); font-style:italic; }
.hmp-text{ font-family:var(--font-body); font-size:.82rem; color:rgba(var(--cream-rgb),.82); max-width:36ch; margin:0; line-height:1.6; }
.hmp-btn{
  margin-top:6px; font-family:var(--font-body); font-weight:700; font-size:.78rem; color:#3d2c0f;
  background:var(--gold); padding:9px 20px; border-radius:999px;
}

.pres-mini-preview, .why-mini-preview{
  background:var(--paper); border:1px solid var(--line); border-radius:16px; padding:24px 26px;
}
.pmp-title, .wmp-title{ font-family:var(--font-display); font-weight:600; font-size:1.18rem; color:var(--ink); margin:0 0 9px; }
.pmp-text, .wmp-text{ font-family:var(--font-body); font-size:.83rem; color:var(--olive); line-height:1.65; margin:0 0 9px; }
.pmp-text:last-child, .wmp-text:last-child{ margin-bottom:0; }
.wmp-eyebrow{
  display:inline-block; font-family:var(--font-body); font-size:.68rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
  color:var(--gold); margin-bottom:7px;
}
.wmp-quote{
  margin:14px 0 0; padding-left:15px; border-left:3px solid var(--gold);
  font-family:var(--font-display); font-style:italic; font-size:.94rem; color:var(--ink); line-height:1.5;
}

.contact-mini-preview{
  background:linear-gradient(160deg, var(--pine-dark) 0%, #2d2115 100%);
  border-radius:16px; padding:26px 24px; display:flex; flex-direction:column; gap:14px;
}
.cmp-location{ font-family:var(--font-body); font-size:.82rem; color:rgba(var(--cream-rgb),.85); text-align:center; }
.cmp-cards{ display:flex; flex-direction:column; gap:8px; }
@media (min-width:480px){ .cmp-cards{ flex-direction:row; } }
.cmp-card{
  flex:1; display:flex; align-items:center; gap:10px; background:rgba(255,255,255,.08);
  border-radius:12px; padding:12px 14px; font-family:var(--font-body); font-size:.82rem; font-weight:600; color:var(--cream);
}
.cmp-card-icon{ font-size:1.1rem; flex:none; }
.cmp-card-text{ word-break:break-word; min-width:0; }
.cmp-social{ display:flex; gap:10px; justify-content:center; }
.cmp-social span{
  font-family:var(--font-body); font-size:.72rem; font-weight:700; color:var(--gold);
  background:rgba(255,255,255,.08); padding:5px 12px; border-radius:999px;
}

.footer-mini-preview{
  background:var(--pine-dark); border-radius:14px; padding:16px 20px;
  display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:8px;
}
.fmp-copy{ font-family:var(--font-body); font-size:.8rem; color:var(--cream); }
.fmp-tagline{ font-family:var(--font-body); font-size:.76rem; color:rgba(var(--cream-rgb),.7); font-style:italic; }

/* ---------------------------------------------------------------------
   Panneau SEO — compteurs de caractères + aperçus (Google / partage
   réseaux sociaux). Purement visuel : n'affecte jamais le contenu
   publié, seulement la compréhension de ce qui va être affiché ailleurs.
   --------------------------------------------------------------------- */
.seo-counter{ font-size:.72rem; font-weight:600; margin-top:5px; color:var(--olive); }
.seo-counter.is-good{ color:var(--ok); }
.seo-counter.is-warn{ color:#b8781f; }
.seo-counter.is-bad{ color:var(--danger); }
.seo-previews{ display:grid; grid-template-columns:1fr; gap:18px; margin:20px 0 6px; }
@media (min-width:820px){ .seo-previews{ grid-template-columns:1fr 1fr; } }
.seo-preview-label{ font-size:.8rem; font-weight:600; color:var(--ink); margin-bottom:8px; }
/* Mimique volontairement le style visuel de Google (police système,
   bleu de lien) plutôt que la charte du site — c'est justement le but :
   montrer à quoi ça ressemble une fois SORTI du site. */
.seo-google-preview{
  border:1px solid var(--line); border-radius:12px; padding:16px 18px 18px; background:#fff;
  font-family:arial, "Segoe UI", sans-serif;
}
.seo-g-site{ display:flex; align-items:center; gap:8px; margin-bottom:6px; }
.seo-g-favicon{
  width:22px; height:22px; border-radius:50%; background:var(--gold-light); color:#5a4726;
  font-size:.68rem; font-weight:700; display:flex; align-items:center; justify-content:center; flex:none;
}
.seo-g-site span{ font-size:.82rem; color:#202124; }
.seo-g-title{ font-size:1.15rem; color:#1a0dab; line-height:1.3; margin-bottom:4px; word-break:break-word; }
.seo-g-desc{ font-size:.85rem; color:#4d5156; line-height:1.5; word-break:break-word; }
.seo-social-preview{
  border:1px solid var(--line); border-radius:12px; overflow:hidden; background:#fff;
}
.seo-s-image{
  width:100%; aspect-ratio:1.91/1; background:var(--paper-soft) center/cover no-repeat;
  display:flex; align-items:center; justify-content:center; color:#a99f8a;
  font-size:.72rem; text-align:center; padding:12px; line-height:1.4;
}
.seo-s-body{ padding:12px 14px; border-top:1px solid var(--line); }
.seo-s-domain{ font-size:.66rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#65676b; margin-bottom:4px; }
.seo-s-title{ font-size:.92rem; font-weight:700; color:#050505; line-height:1.3; margin-bottom:3px; word-break:break-word; }
.seo-s-desc{
  font-size:.8rem; color:#65676b; line-height:1.4; word-break:break-word;
  display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
}

/* ---------------------------------------------------------------------
   Type d'affichage de la galerie — cartes à choix visuel (même famille
   que les préréglages de couleurs), chacune avec une mini-maquette CSS
   pure représentant le style plutôt qu'un mot seul, pour comprendre le
   rendu d'un coup d'œil sans avoir à publier pour tester.
   --------------------------------------------------------------------- */
.gallery-layout-picker{ display:grid; grid-template-columns:repeat(auto-fill, minmax(168px,1fr)); gap:14px; margin-bottom:18px; }
.gallery-layout-option{
  display:flex; flex-direction:column; align-items:center; gap:9px; padding:14px 12px 12px;
  border:1.5px solid var(--line); border-radius:16px; background:#fffdf9; cursor:pointer;
  font-family:var(--font-body); text-align:center; position:relative; overflow:hidden;
  transition:border-color .18s ease, transform .18s cubic-bezier(.34,1.56,.64,1), box-shadow .18s ease;
}
.gallery-layout-option:hover{ border-color:var(--gold); transform:translateY(-2px); box-shadow:0 10px 24px rgba(var(--ink-rgb),.1); }
.gallery-layout-option:hover .gallery-layout-preview span{ transform:scale(1.05); }
.gallery-layout-option:active{ transform:scale(.97); }
.gallery-layout-option.is-current{ border-color:var(--gold); background:var(--paper-soft); box-shadow:0 0 0 3px rgba(171,143,102,.18); }
.gallery-layout-option.is-current::before{
  content:""; position:absolute; inset:0; pointer-events:none; border-radius:16px;
  background:radial-gradient(120% 90% at 50% -10%, rgba(171,143,102,.14), transparent 60%);
}
.gallery-layout-option.is-current::after{
  content:"✓"; position:absolute; top:-6px; right:-6px; width:20px; height:20px; border-radius:50%;
  background:var(--gold); color:#fff; font-size:.66rem; font-weight:800; display:flex; align-items:center; justify-content:center;
  box-shadow:0 2px 6px rgba(var(--ink-rgb),.3); animation:tpLabelIn .18s ease both;
}
/* Mini-maquette façon mosaïque de photos — un dégradé de teintes chaudes
   différent par tuile plutôt qu'un aplat unique, pour évoquer un vrai
   assemblage de photos plutôt qu'un simple diagramme abstrait. */
.gallery-layout-preview{ width:100%; height:56px; display:grid; gap:3px; }
.gallery-layout-preview span{
  border-radius:5px; transition:transform .25s cubic-bezier(.34,1.56,.64,1);
  background-size:cover;
}
.gallery-layout-preview span:nth-child(8n+1){ background:linear-gradient(155deg,var(--gold-light),var(--gold)); }
.gallery-layout-preview span:nth-child(8n+2){ background:linear-gradient(155deg,var(--pine),var(--pine-dark)); }
.gallery-layout-preview span:nth-child(8n+3){ background:linear-gradient(155deg,var(--gold),var(--olive)); }
.gallery-layout-preview span:nth-child(8n+4){ background:linear-gradient(155deg,var(--pine-dark),var(--ink)); }
.gallery-layout-preview span:nth-child(8n+5){ background:linear-gradient(155deg,var(--olive),var(--pine)); }
.gallery-layout-preview span:nth-child(8n+6){ background:linear-gradient(155deg,var(--gold-light),var(--olive)); }
.gallery-layout-preview span:nth-child(8n+7){ background:linear-gradient(155deg,var(--pine),var(--gold)); }
.gallery-layout-preview span:nth-child(8n+8){ background:linear-gradient(155deg,var(--gold),var(--pine-dark)); }
.gallery-layout-preview.gl-grid{ grid-template-columns:repeat(4,1fr); grid-template-rows:repeat(2,1fr); }
.gallery-layout-preview.gl-mosaic{ grid-template-columns:repeat(4,1fr); grid-template-rows:repeat(2,1fr); }
.gallery-layout-preview.gl-mosaic span:first-child{ grid-column:span 2; grid-row:span 2; }
.gallery-layout-preview.gl-carousel{ grid-auto-flow:column; grid-template-columns:repeat(5,26px); grid-template-rows:1fr; justify-content:center; position:relative; }
/* Petit indice animé façon défilement, pour suggérer le mouvement propre
   au carrousel sans avoir à l'expliquer en mots. */
.gallery-layout-option:hover .gallery-layout-preview.gl-carousel span{ animation:glCarouselHint 1.1s ease-in-out; animation-fill-mode:backwards; }
.gallery-layout-preview.gl-carousel span:nth-child(1){ animation-delay:0s; }
.gallery-layout-preview.gl-carousel span:nth-child(2){ animation-delay:.05s; }
.gallery-layout-preview.gl-carousel span:nth-child(3){ animation-delay:.1s; }
.gallery-layout-preview.gl-carousel span:nth-child(4){ animation-delay:.15s; }
.gallery-layout-preview.gl-carousel span:nth-child(5){ animation-delay:.2s; }
@keyframes glCarouselHint{ 0%{ transform:translateX(6px); } 60%{ transform:translateX(-3px); } 100%{ transform:translateX(0); } }
@media (prefers-reduced-motion: reduce){
  .gallery-layout-option:hover .gallery-layout-preview.gl-carousel span{ animation:none; }
}
.gallery-layout-option-label{ font-size:.84rem; font-weight:700; color:var(--ink); }
.gallery-layout-option-desc{ font-size:.7rem; color:var(--olive); line-height:1.4; }
.gallery-layout-option-tag{
  font-size:.62rem; font-weight:700; letter-spacing:.02em; color:var(--pine);
  background:rgba(var(--pine-dark-rgb),.1); border-radius:999px; padding:2px 9px; margin-top:1px;
}
/* Aperçu simplifié de la galerie — reprend les VRAIES photos (pas des
   couleurs abstraites comme dans le sélecteur ci-dessus) avec la mise
   en page réellement choisie, en miniature, pour voir le résultat sans
   avoir à ouvrir « Aperçu du site ». Les mêmes classes CSS que le site
   (is-mosaic / is-carousel) sont réutilisées, juste à une échelle plus
   petite, pour rester fidèle au vrai rendu plutôt qu'une approximation. */
.gallery-live-preview-wrap{ margin:6px 0 20px; }
.gallery-live-preview-label{ font-size:.78rem; font-weight:600; color:var(--ink); margin-bottom:8px; }
.gallery-live-preview{
  display:grid; grid-template-columns:repeat(auto-fill, minmax(64px,1fr)); gap:6px;
  padding:12px; border-radius:12px; background:var(--paper-soft); border:1px solid var(--line);
}
.gallery-live-preview.is-mosaic{ grid-auto-flow:dense; }
/* aspect-ratio:auto retiré ici à dessein : ça laissait la hauteur de la
   tuile vedette se caler sur le ratio intrinsèque de sa photo au lieu de
   rester carrée sur sa zone 2×2 (même correctif que sur le site public,
   .gallery-grid.is-mosaic .gallery-item:nth-child(6n+1) dans index.html).
   Avec une photo "haute" (ex. capture d'écran), la tuile devenait un
   rectangle bien plus haut que large et débordait de sa zone 2×2. En
   gardant aspect-ratio:1/1 (hérité de .glp-item ci-dessous), la tuile
   vedette reste carrée comme les autres, quel que soit le format de la
   photo d'origine. */
.gallery-live-preview.is-mosaic .glp-item:nth-child(6n+1){ grid-column:span 2; grid-row:span 2; }
.gallery-live-preview.is-carousel{ display:flex; gap:6px; overflow-x:auto; padding-bottom:8px; }
.gallery-live-preview.is-carousel .glp-item{ flex:0 0 auto; width:64px; }
.glp-item{
  aspect-ratio:1/1; border-radius:6px; overflow:hidden; position:relative;
  background:linear-gradient(155deg,var(--gold-light) 0%, var(--pine) 60%, var(--pine-dark) 100%);
}
.glp-item img{ width:100%; height:100%; object-fit:cover; display:block; }
.glp-item img.img-error{ display:none; }
.gallery-live-preview-empty{ font-size:.8rem; color:var(--olive); text-align:center; padding:14px 8px; grid-column:1/-1; }
.gallery-live-preview-more{
  display:flex; align-items:center; justify-content:center; aspect-ratio:1/1; border-radius:6px;
  background:rgba(var(--ink-rgb),.08); color:var(--olive); font-size:.72rem; font-weight:700;
}

/* ---------------------------------------------------------------------
   Aperçu du site (iframe plein écran)
   --------------------------------------------------------------------- */
.preview-overlay{
  position:fixed; inset:0; z-index:500; background:#2b2620;
  display:flex; flex-direction:column;
}
.preview-topbar{
  display:flex; align-items:center; justify-content:space-between; gap:14px;
  padding:10px 16px; padding-top:calc(10px + env(safe-area-inset-top));
  background:var(--paper); border-bottom:1px solid var(--line); flex-wrap:wrap;
}
.preview-topbar-left{ display:flex; flex-direction:column; gap:2px; min-width:0; flex:1 1 auto; }
.preview-topbar-left strong{ font-family:var(--font-display); font-size:1rem; }
.preview-topbar-left .desc{ margin:0; max-width:min(900px, 100%); }
.preview-topbar-actions{ display:flex; gap:8px; flex-wrap:wrap; }
.preview-frame-wrap{
  flex:1; overflow:auto; display:flex; justify-content:center; align-items:flex-start;
  background:#2b2620; padding:18px;
}
#previewIframe{ border:none; background:#fff; width:100%; height:100%; border-radius:10px; box-shadow:0 14px 44px rgba(0,0,0,.4); }
.preview-frame-wrap.mobile{ align-items:center; }
.preview-frame-wrap.mobile #previewIframe{ width:min(390px, calc(100vw - 40px)); min-width:0; height:820px; border-radius:28px; }

/* ---------------------------------------------------------------------
   Guide complet — fiche explicative pas à pas (bouton « Guide complet »
   dans l'en-tête). Même famille visuelle que l'aperçu du site, en plein
   écran, avec un sommaire cliquable et des étapes numérotées.
   --------------------------------------------------------------------- */
.guide-overlay{
  position:fixed; inset:0; z-index:600; background:var(--paper);
  display:flex; flex-direction:column;
  opacity:0; transform:scale(.98);
  transition:opacity .3s ease, transform .34s cubic-bezier(.34,1.15,.4,1);
}
.guide-overlay.show{ opacity:1; transform:scale(1); }

/* Fine progress bar — reflète la position de lecture dans le guide */
.guide-progress{ height:3px; background:var(--line); flex:none; overflow:hidden; }
.guide-progress-fill{
  height:100%; width:0%; border-radius:0 3px 3px 0;
  background:linear-gradient(90deg,var(--gold-light),var(--gold));
  transition:width .12s linear;
}
.guide-topbar{
  display:flex; align-items:center; justify-content:space-between; gap:14px;
  padding:12px 22px; padding-top:calc(12px + env(safe-area-inset-top));
  background:linear-gradient(135deg,var(--gold-light) 0%,var(--paper-soft) 65%);
  border-bottom:1px solid var(--line); flex-wrap:nowrap;
  transition:padding .3s ease;
}
.guide-topbar-left{ display:flex; flex-direction:column; gap:4px; min-width:0; flex:1 1 auto; }
.guide-topbar-left strong{
  font-family:var(--font-display); font-size:1.25rem; line-height:1.2;
  display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.guide-topbar-left .desc{ margin:0; max-width:min(960px, 100%); overflow:hidden; max-height:200px; opacity:1; transition:max-height .3s ease, opacity .25s ease, margin .3s ease; }
.guide-topbar-actions{ display:flex; align-items:center; gap:8px; flex:none; }
.guide-collapse-chevron{ transition:transform .3s cubic-bezier(.4,0,.2,1); flex:none; }
.guide-overlay.guide-collapsed .guide-collapse-chevron{ transform:rotate(180deg); }
.guide-overlay.guide-collapsed .guide-topbar-left .desc{ max-height:0; opacity:0; }
.guide-overlay.guide-collapsed .guide-topbar{ padding-top:calc(10px + env(safe-area-inset-top)); padding-bottom:10px; }
.guide-collapse-label{ display:inline-block; }
.guide-overlay.guide-collapsed .guide-collapse-label{ display:none; }
.guide-body{ flex:1; overflow-y:auto; -webkit-overflow-scrolling:touch; }
.guide-inner{ max-width:820px; margin:0 auto; padding:28px 20px 90px; }
/* Même logique que .container : le guide plein écran reste lisible sans
   laisser un vide immense de chaque côté sur les grands écrans. */
@media (min-width:1440px){ .guide-inner{ max-width:960px; } }
@media (min-width:1800px){ .guide-inner{ max-width:1100px; } }
@media (min-width:2200px){ .guide-inner{ max-width:1240px; } }

.guide-toc{
  display:flex; flex-wrap:wrap; gap:8px; margin-bottom:34px;
  padding:14px; border:1px solid var(--line); border-radius:var(--radius); background:var(--cream);
}
.guide-toc-label{ width:100%; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:var(--olive); margin-bottom:2px; }
.guide-toc a{
  font-size:.82rem; font-weight:600; color:var(--olive); text-decoration:none;
  padding:7px 13px; border-radius:999px; background:var(--paper-soft); border:1px solid var(--line);
  transition:background .18s ease, color .18s ease, border-color .18s ease, transform .18s ease;
}
.guide-toc a:hover{ background:var(--gold-light); color:var(--ink); border-color:var(--gold); transform:translateY(-1px); }

.guide-step{ display:flex; gap:18px; margin-bottom:40px; scroll-margin-top:18px; }
.guide-step-num{
  flex:none; width:42px; height:42px; border-radius:50%;
  background:linear-gradient(135deg,var(--gold-light) 0%,var(--gold) 100%);
  color:#3d2c0f; font-family:var(--font-display); font-weight:700; font-size:1.15rem;
  display:flex; align-items:center; justify-content:center;
  box-shadow:0 6px 16px rgba(171,143,102,.35);
}
.guide-step-body{ min-width:0; flex:1; }
.guide-step h3{ margin:2px 0 12px; font-size:1.18rem; display:flex; align-items:center; gap:9px; flex-wrap:wrap; }
.guide-step p{ font-size:.95rem; }
.guide-step ol, .guide-step ul{ margin:0 0 14px; padding-left:22px; font-size:.95rem; }
.guide-step li{ margin-bottom:9px; }
.guide-step li::marker{ color:var(--gold); font-weight:700; }
.guide-step code{ background:var(--paper-soft); border:1px solid var(--line); border-radius:6px; padding:1px 7px; font-size:.86em; }

.guide-callout{
  display:flex; gap:12px; padding:14px 16px; border-radius:14px; margin:14px 0;
  border:1px solid var(--line); background:var(--cream); font-size:.88rem;
}
.guide-callout-icon{ flex:none; font-size:1.25rem; line-height:1.3; }
.guide-callout-title{ font-weight:700; display:block; margin-bottom:2px; }
.guide-callout-tip{ border-color:var(--gold); background:var(--gold-light); }
.guide-callout-warn{ border-color:var(--danger); background:var(--danger-light); }

.guide-checklist{
  border:2px dashed var(--gold); border-radius:var(--radius); padding:22px 24px; background:var(--cream);
  margin-top:8px;
}
.guide-checklist h3{ margin-top:0; }
.guide-checklist label{ display:flex; align-items:flex-start; gap:10px; font-size:.92rem; margin-bottom:11px; cursor:pointer; }
.guide-checklist input{ margin-top:3px; flex:none; width:16px; height:16px; accent-color:var(--gold); }
.guide-checklist label:last-child{ margin-bottom:0; }

.guide-callout-info{ border-color:#9dc0ef; background:#eaf2fd; }

/* ---------------------------------------------------------------------
   Petits schémas visuels (flux d'étapes, arborescence de fichiers,
   aperçu de champ, cartes de conseils) — pour rendre le guide concret
   sans dépendre de captures d'écran qui se démoderaient à la moindre
   évolution de l'admin.
   --------------------------------------------------------------------- */
.guide-flow{ display:flex; flex-wrap:wrap; align-items:stretch; gap:0; margin:16px 0 20px; }
.guide-flow-step{
  flex:1 1 160px; min-width:150px; display:flex; flex-direction:column; gap:6px;
  border:1px solid var(--line); border-radius:14px; background:#fff; padding:14px 16px; text-align:left;
}
.guide-flow-step-icon{ font-size:1.4rem; line-height:1; }
.guide-flow-step-title{ font-weight:700; font-size:.88rem; color:var(--ink); }
.guide-flow-step-text{ font-size:.8rem; color:var(--olive); line-height:1.5; }
.guide-flow-arrow{ flex:none; display:flex; align-items:center; justify-content:center; width:34px; font-size:1.2rem; color:var(--gold); font-weight:700; }
@media (max-width:720px){
  .guide-flow{ flex-direction:column; }
  .guide-flow-arrow{ width:auto; height:22px; transform:rotate(90deg); }
}

.guide-tree{
  font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-size:.82rem; line-height:1.9;
  background:#fffdf9; border:1px solid var(--line); border-radius:12px; padding:14px 18px; margin:12px 0 18px;
  color:var(--ink); overflow-x:auto;
}
.guide-tree .gt-dim{ color:#a89b83; }
.guide-tree .gt-file{ color:var(--pine-dark); font-weight:600; }
.guide-tree .gt-folder{ color:#7a5a1e; font-weight:700; }

.guide-kbd{
  display:inline-block; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
  font-size:.78rem; padding:2px 7px; border-radius:6px; border:1px solid var(--line);
  border-bottom-width:2px; background:#fff; color:var(--ink); font-weight:600;
}

.guide-badge-new{
  display:inline-block; font-size:.62rem; font-weight:700; letter-spacing:.05em; text-transform:uppercase;
  color:#fff; background:var(--ok); border-radius:999px; padding:2px 8px; vertical-align:middle; margin-left:6px;
}

.guide-mock-field{
  display:flex; align-items:center; gap:6px; border:1px solid var(--line); border-radius:10px;
  background:#fff; padding:8px 8px 8px 12px; margin:10px 0; font-size:.85rem; max-width:440px;
}
.guide-mock-field-path{ color:#a89b83; }
.guide-mock-field-name{ flex:1; color:var(--ink); font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; }
.guide-mock-btn{
  flex:none; width:30px; height:30px; border-radius:8px; border:1px solid var(--line); background:var(--paper-soft);
  display:flex; align-items:center; justify-content:center; font-size:.95rem;
}

.guide-tip-grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(230px,1fr)); gap:14px; margin:14px 0 22px; }
.guide-tip-card{ border:1px solid var(--line); border-radius:14px; padding:15px 17px; background:#fff; }
.guide-tip-card h4{ margin:0 0 6px; font-size:.92rem; display:flex; align-items:center; gap:7px; }
.guide-tip-card p{ margin:0; font-size:.84rem; line-height:1.55; color:var(--olive); }
.guide-tip-card{ transition:transform .22s cubic-bezier(.34,1.56,.64,1), box-shadow .22s ease, border-color .22s ease; }
.guide-tip-card:hover{ transform:translateY(-3px); box-shadow:0 12px 26px rgba(var(--ink-rgb),.08); border-color:var(--gold); }
.guide-fix{ transition:transform .2s ease, box-shadow .2s ease, border-left-color .2s ease; }
.guide-fix:hover{ transform:translateX(2px); box-shadow:0 8px 20px rgba(var(--ink-rgb),.06); border-left-color:#8a3a28; }

/* Connecteur « ou » entre deux façons équivalentes de faire quelque
   chose (par opposition à la flèche → qui indique un enchaînement). */
.guide-flow-or{
  flex:none; display:flex; align-items:center; justify-content:center; width:34px;
  font-size:.7rem; font-weight:700; color:var(--olive); text-transform:uppercase; letter-spacing:.04em;
}
@media (max-width:720px){ .guide-flow-or{ width:auto; height:20px; } }

/* Icônes de guide-flow-step : petit rebond au survol, purement ludique */
.guide-flow-step{ transition:transform .22s cubic-bezier(.34,1.56,.64,1), box-shadow .22s ease, border-color .22s ease; }
.guide-flow-step:hover{ transform:translateY(-3px); box-shadow:0 12px 26px rgba(var(--ink-rgb),.08); border-color:var(--gold); }
.guide-flow-step:hover .guide-flow-step-icon{ animation:guideIconBounce .5s ease; }
@keyframes guideIconBounce{ 0%,100%{ transform:translateY(0); } 40%{ transform:translateY(-4px) rotate(-6deg); } 70%{ transform:translateY(1px) rotate(3deg); } }

/* Sommaire : lien de la section actuellement lue, mis à jour au scroll */
.guide-toc a.active{ background:var(--gold); color:#fff; border-color:var(--gold); }

/* Apparition progressive des étapes au scroll (ajoutée uniquement par
   JS via la classe gr-pre : sans JavaScript, tout reste visible). */
.gr-pre{ opacity:0; transform:translateY(18px); transition:opacity .5s ease, transform .5s cubic-bezier(.22,.9,.3,1); }
.gr-pre.gr-in{ opacity:1; transform:translateY(0); }

/* Aide-mémoire : petite fête une fois les 4 cases cochées */
.guide-checklist{ transition:border-color .3s ease, box-shadow .3s ease; }
.guide-checklist.is-complete{ border-style:solid; border-color:var(--ok); box-shadow:0 10px 30px rgba(95,122,79,.18); }
.guide-checklist-done{
  display:inline-block; margin-left:8px; font-family:var(--font-body); font-size:.78rem; font-weight:700;
  color:var(--ok); opacity:0; transform:translateX(-6px); transition:opacity .3s ease, transform .3s ease;
}
.guide-checklist.is-complete .guide-checklist-done{ opacity:1; transform:translateX(0); }
.guide-checklist.celebrate{ animation:guideChecklistCelebrate .6s cubic-bezier(.34,1.56,.64,1); }
@keyframes guideChecklistCelebrate{ 0%{ transform:scale(1); } 35%{ transform:scale(1.015); } 65%{ transform:scale(.998); } 100%{ transform:scale(1); } }

@media (max-width:640px){
  .guide-progress{ height:2px; }
  .guide-flow-step, .guide-tip-card{ min-width:0; }
}

/* ---------------------------------------------------------------------
   Blocs "content.json" — présentés comme une mini fenêtre de code
   (barre de titre à pastilles + corps avec coloration syntaxique
   légère injectée en JS via highlightGuideJson()) plutôt qu'un pavé
   de texte brut qui débordait horizontalement.
   --------------------------------------------------------------------- */
.guide-json-window{
  border:1px solid var(--line); border-radius:12px; overflow:hidden;
  margin:10px 0 16px; box-shadow:0 6px 18px rgba(var(--ink-rgb),.05);
  background:#fffdf9;
}
.guide-json-bar{
  display:flex; align-items:center; gap:7px; padding:8px 12px;
  background:linear-gradient(135deg,var(--paper-soft) 0%,var(--gold-light) 160%);
  border-bottom:1px solid var(--line);
}
.guide-json-dot{ width:8px; height:8px; border-radius:50%; flex:none; opacity:.85; }
.guide-json-label{
  margin-left:auto; font-family:var(--font-body); font-weight:700; font-size:.68rem;
  letter-spacing:.06em; text-transform:uppercase; color:var(--olive);
}
.guide-json{
  display:block; background:transparent; border:none; border-radius:0;
  padding:13px 15px; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
  font-size:.8rem; line-height:1.65; white-space:pre-wrap; word-break:break-word;
  overflow-wrap:anywhere; margin:0; color:var(--ink); max-height:260px; overflow-y:auto;
}
.gj-key{ color:#7a5a1e; font-weight:700; }
.gj-string{ color:var(--pine-dark); }
.gj-bool-true{ color:var(--ok); font-weight:700; }
.gj-bool-false{ color:var(--danger); font-weight:700; }
.gj-comment{ color:#a89b83; font-style:italic; }

.guide-panel-grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(270px,1fr)); gap:16px; margin:10px 0 24px; }
.guide-panel-card{
  border:1px solid var(--line); border-radius:14px; padding:17px 19px; background:#fff; min-width:0;
  position:relative; overflow:hidden;
  transition:transform .22s cubic-bezier(.34,1.56,.64,1), box-shadow .22s ease, border-color .22s ease;
}
.guide-panel-card::before{
  content:""; position:absolute; top:0; left:0; right:0; height:3px; border-radius:14px 14px 0 0;
  background:linear-gradient(90deg,var(--gold-light),var(--gold)); opacity:0; transition:opacity .22s ease;
}
.guide-panel-card:hover{ transform:translateY(-3px); box-shadow:0 12px 28px rgba(var(--ink-rgb),.09); border-color:var(--gold); }
.guide-panel-card:hover::before{ opacity:1; }
.guide-panel-card h4{
  margin:0 0 8px; font-family:var(--font-display); font-size:1.04rem; font-weight:600;
  display:flex; align-items:center; gap:7px; flex-wrap:wrap;
}
.guide-panel-card .guide-path{
  display:inline-block; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
  font-size:.71rem; font-weight:700; letter-spacing:.02em;
  color:#7a5a1e; background:var(--gold-light); border:1px solid rgba(171,143,102,.4);
  border-radius:6px; padding:3px 8px; margin:0 0 10px; word-break:break-all;
}
.guide-panel-card p{ margin:0 0 8px; font-size:.88rem; line-height:1.55; }
.guide-panel-card p:last-child{ margin-bottom:0; }

/* ---------------------------------------------------------------------
   Structure du site — présentée comme une petite frise numérotée
   (le compteur CSS suit l'ordre réel d'affichage des sections)
   --------------------------------------------------------------------- */
.guide-site-list{
  counter-reset:site-section; list-style:none; margin:12px 0 22px; padding:0;
  display:flex; flex-direction:column; gap:9px;
}
.guide-site-list li{
  counter-increment:site-section; position:relative;
  border:1px solid var(--line); border-left:3px solid var(--gold-light); border-radius:12px;
  padding:12px 16px 12px 50px; font-size:.88rem; line-height:1.55;
  display:flex; flex-direction:column; gap:5px; background:#fff;
  transition:border-color .2s ease, box-shadow .2s ease, transform .2s ease;
}
.guide-site-list li:hover{ border-left-color:var(--gold); box-shadow:0 8px 20px rgba(var(--ink-rgb),.06); transform:translateX(2px); }
.guide-site-list li::before{
  content:counter(site-section); position:absolute; left:14px; top:12px;
  width:22px; height:22px; border-radius:50%; background:var(--gold-light); color:#5a4726;
  font-family:var(--font-display); font-weight:700; font-size:.76rem;
  display:flex; align-items:center; justify-content:center; flex:none;
}
.guide-site-list .guide-path{
  align-self:flex-start; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
  font-size:.72rem; font-weight:700; letter-spacing:.03em; color:#7a5a1e;
  background:var(--gold-light); border:1px solid rgba(171,143,102,.4); border-radius:6px; padding:2px 8px;
}

/* ---------------------------------------------------------------------
   Problèmes potentiels — chaque cas devient une fiche repérable au
   lieu d'un bloc de texte séparé par un simple trait pointillé
   --------------------------------------------------------------------- */
.guide-fix{
  margin-bottom:12px; padding:14px 16px; border-radius:12px;
  background:var(--cream); border:1px solid var(--line); border-left:3px solid var(--danger);
}
.guide-fix:last-child{ margin-bottom:0; }
.guide-fix .guide-fix-title{
  display:flex; align-items:center; gap:7px; font-weight:700; margin-bottom:6px; font-size:.93rem;
}
.guide-fix .guide-fix-title::before{ content:"🩹"; font-size:.88rem; line-height:1; }
.guide-fix p{ margin:0 0 4px; font-size:.88rem; line-height:1.55; }
.guide-fix p:last-child{ margin-bottom:0; }

@media (max-width:640px){
  .guide-panel-grid{ grid-template-columns:1fr; }
  .guide-json{ font-size:.74rem; max-height:220px; }
  .guide-site-list li{ padding-left:44px; }
}

@media (max-width:640px){
  .guide-topbar{ padding:10px 12px; padding-top:calc(10px + env(safe-area-inset-top)); gap:8px; }
  .guide-topbar-left strong{ font-size:1rem; }
  .guide-topbar-left .desc{ display:none; }
  .guide-topbar-actions{ gap:6px; }
  .guide-collapse-label{ display:none; }
  .guide-step{ gap:12px; }
  .guide-step-num{ width:34px; height:34px; font-size:1rem; }
}

/* ---------------------------------------------------------------------
   Confirmation personnalisée — remplace window.confirm()
   --------------------------------------------------------------------- */
.confirm-overlay{
  /* z-index volontairement au-dessus de .diff-overlay (950) : cette
     boîte de confirmation peut être ouverte DEPUIS un autre modal déjà
     affiché (ex. re-saisir le mot de passe pour copier une photo depuis
     le sélecteur d'images 🖼️, lui-même en .diff-overlay) — si elle
     passait dessous, elle resterait invisible et inaccessible, et
     l'action semblerait bloquée indéfiniment sans aucun message d'erreur
     (bug confirmé : "Copie en cours…" qui ne se termine jamais). */
  position:fixed; inset:0; z-index:1000;
  display:flex; align-items:flex-end; justify-content:center;
  padding:16px; padding-bottom:calc(16px + env(safe-area-inset-bottom));
  background:rgba(43,38,32,0);
  backdrop-filter:blur(0px); -webkit-backdrop-filter:blur(0px);
  transition:background .3s ease, backdrop-filter .3s ease;
}
.confirm-overlay.show{
  background:rgba(43,38,32,.55);
  backdrop-filter:blur(5px); -webkit-backdrop-filter:blur(5px);
}
@media(min-width:560px){
  .confirm-overlay{ align-items:center; }
}
.confirm-modal{
  width:100%; max-width:400px;
  background:var(--paper);
  border:1px solid var(--line);
  border-radius:calc(var(--radius) + 6px);
  padding:32px 26px 24px;
  text-align:center;
  box-shadow:0 30px 80px rgba(var(--ink-rgb),.4), 0 2px 0 rgba(255,255,255,.5) inset;
  transform:translateY(60px) scale(.85); opacity:0;
  transition:transform .42s cubic-bezier(.34,1.56,.64,1), opacity .26s ease;
}
.confirm-overlay.show .confirm-modal{
  transform:translateY(0) scale(1); opacity:1;
}
.confirm-modal.is-shake{ animation:confirmShake .45s ease; }
@keyframes confirmShake{
  0%,100%{ transform:translateX(0) scale(1); }
  20%{ transform:translateX(-9px) scale(1); }
  40%{ transform:translateX(8px) scale(1); }
  60%{ transform:translateX(-5px) scale(1); }
  80%{ transform:translateX(3px) scale(1); }
}
.confirm-icon{
  width:60px; height:60px; margin:0 auto 16px;
  border-radius:50%;
  display:flex; align-items:center; justify-content:center;
  font-size:1.6rem; line-height:1;
  background:rgba(var(--pine-dark-rgb),.16); color:var(--gold);
  animation:confirmIconPop .5s cubic-bezier(.34,1.56,.64,1) .08s both;
}
.confirm-modal.is-danger .confirm-icon{ background:var(--danger-light); color:var(--danger); }
@keyframes confirmIconPop{
  from{ transform:scale(.2) rotate(-24deg); opacity:0; }
  to{ transform:scale(1) rotate(0); opacity:1; }
}
.confirm-title{
  font-family:var(--font-display); font-weight:700; font-size:1.26rem;
  margin:0 0 10px; color:var(--ink); letter-spacing:-.01em;
}
.confirm-message{
  font-size:.94rem; line-height:1.6; color:var(--olive);
  margin:0 0 26px; white-space:pre-line;
}
.confirm-actions{ display:flex; gap:10px; }
.confirm-actions .btn{ flex:1; min-width:0; white-space:normal; text-align:center; line-height:1.25; }

/* ---------------------------------------------------------------------
   Reconfirmation du mot de passe avant publication — même famille
   visuelle que .confirm-modal, avec un champ de saisie en plus.
   --------------------------------------------------------------------- */
.pw-confirm-icon{ background:rgba(171,143,102,.16); color:var(--gold); }
.pw-confirm-form{ text-align:left; margin-top:4px; }
.pw-confirm-form label{ display:block; font-size:.85rem; font-weight:600; margin-bottom:6px; color:var(--ink); }
.pw-confirm-input-wrap{ position:relative; }
.pw-confirm-form input[type="password"], .pw-confirm-form input[type="text"]{
  width:100%; padding:12px 44px 12px 14px; border:1px solid var(--line); border-radius:10px;
  font-size:1rem; font-family:inherit; background:#fff; color:var(--ink);
  transition:border-color .15s ease, box-shadow .15s ease;
}
.pw-confirm-form input:focus{ outline:none; border-color:var(--gold); box-shadow:0 0 0 3px rgba(171,143,102,.18); }
.pw-confirm-form input.is-error{ border-color:var(--danger); }
.pw-confirm-toggle{
  position:absolute; right:6px; top:50%; transform:translateY(-50%);
  width:32px; height:32px; border:none; background:transparent; border-radius:8px;
  cursor:pointer; font-size:1.05rem; color:var(--olive); display:flex; align-items:center; justify-content:center;
}
.pw-confirm-toggle:hover{ background:var(--paper-soft); }
.pw-confirm-error{
  margin-top:10px; padding:10px 12px; border-radius:8px; background:var(--danger-light); color:#8a3a28;
  font-size:.85rem; line-height:1.45; display:none;
}
.pw-confirm-hint{ margin:8px 0 0; font-size:.78rem; color:var(--pine-dark); }

/* ---------------------------------------------------------------------
   Confirmation du nom de fichier avant envoi d'une nouvelle photo —
   même famille visuelle que .confirm-modal. Un aperçu de la photo tout
   juste choisie (+ son nom d'origine) reste visible pendant que le nom
   proposé est modifiable, pour qu'il n'y ait jamais de doute sur la
   photo à laquelle ce nom va correspondre une fois envoyée.
   --------------------------------------------------------------------- */
.upload-name-photo{
  width:92px; height:92px; margin:0 auto 12px; border-radius:16px;
  object-fit:cover; display:block; border:1.5px solid var(--line); background:var(--paper-soft);
  box-shadow:0 8px 20px rgba(var(--ink-rgb),.18);
  animation:confirmIconPop .5s cubic-bezier(.34,1.56,.64,1) .08s both;
}
.upload-name-photo.is-broken{ object-fit:contain; padding:16px; opacity:.75; }
.upload-name-original{
  font-size:.78rem; color:var(--olive); margin:0 0 20px; word-break:break-all; line-height:1.4;
}
.upload-name-original strong{ color:var(--ink); font-weight:600; }
.upload-name-form{ text-align:left; margin-top:4px; }
.upload-name-form label{ display:block; font-size:.85rem; font-weight:600; margin-bottom:6px; color:var(--ink); }
.upload-name-form .filepath-row{ margin-bottom:0; }
.upload-name-hint{ margin:8px 0 0; font-size:.78rem; color:var(--pine-dark); }

/* ===================== MODALE DE COMPARAISON DES CHANGEMENTS ===================== */
@keyframes diffPop{
  0%{ transform:scale(.4) rotate(-18deg); opacity:0; }
  55%{ transform:scale(1.14) rotate(4deg); opacity:1; }
  100%{ transform:scale(1) rotate(0); opacity:1; }
}
@keyframes diffGlow{
  0%,100%{ box-shadow:0 0 0 0 rgba(var(--pine-dark-rgb),.35); }
  50%{ box-shadow:0 0 0 10px rgba(var(--pine-dark-rgb),0); }
}
@keyframes diffRowIn{
  from{ transform:translateX(-8px); opacity:0; }
  to{ transform:translateX(0); opacity:1; }
}
@keyframes diffPillIn{
  from{ transform:scale(.5); opacity:0; }
  to{ transform:scale(1); opacity:1; }
}
@media (prefers-reduced-motion: reduce){
  .diff-head-icon, .diff-row, .diff-stat{ animation:none !important; }
}

.diff-overlay{
  position:fixed; inset:0; z-index:950;
  display:flex; align-items:flex-end; justify-content:center;
  padding:0;
  background:rgba(43,38,32,0);
  backdrop-filter:blur(0px); -webkit-backdrop-filter:blur(0px);
  transition:background .32s ease, backdrop-filter .32s ease;
}
.diff-overlay.show{
  background:rgba(43,38,32,.62);
  backdrop-filter:blur(7px); -webkit-backdrop-filter:blur(7px);
}
@media(min-width:640px){
  .diff-overlay{ align-items:center; padding:24px; }
}

.diff-modal{
  width:100%; max-width:min(94vw, 640px); max-height:92vh; max-height:92dvh;
  display:flex; flex-direction:column;
  background:var(--paper);
  border-radius:26px 26px 0 0;
  overflow:hidden;
  box-shadow:0 30px 90px rgba(var(--ink-rgb),.42), 0 2px 0 rgba(255,255,255,.5) inset;
  transform:translateY(70px); opacity:0;
  transition:transform .46s cubic-bezier(.22,1.4,.36,1), opacity .28s ease;
}
@media(min-width:640px){
  .diff-modal{ border-radius:26px; transform:translateY(30px) scale(.94); max-width:min(90vw,680px); }
}
@media(min-width:1024px){ .diff-modal{ max-width:720px; } }
@media(min-width:1440px){ .diff-modal{ max-width:780px; max-height:86vh; max-height:86dvh; } }
@media(min-width:1800px){ .diff-modal{ max-width:860px; } }
@media(min-width:2200px){ .diff-modal{ max-width:960px; } }
@media(min-width:2600px){ .diff-modal{ max-width:1060px; } }
.diff-overlay.show .diff-modal{ transform:translateY(0) scale(1); opacity:1; }

.diff-head{
  position:relative; overflow:hidden;
  padding:28px 24px 20px; flex:none;
  background:
    radial-gradient(120% 160% at 0% 0%, rgba(var(--pine-dark-rgb),.16), transparent 60%),
    linear-gradient(135deg, var(--paper-soft), var(--paper) 70%);
  border-bottom:1px solid var(--line);
}
.diff-head-row{ display:flex; align-items:flex-start; gap:14px; }
.diff-head-icon{
  width:52px; height:52px; border-radius:50%; flex:none;
  display:flex; align-items:center; justify-content:center; font-size:1.6rem;
  background:rgba(var(--pine-dark-rgb),.18); color:var(--gold);
  animation:diffPop .55s cubic-bezier(.22,1.4,.36,1) .05s both, diffGlow 2.6s ease-in-out .6s infinite;
}
.diff-head-text h3{ font-family:var(--font-display); font-weight:700; font-size:clamp(1.18rem, 1.05rem + .5vw, 1.55rem); margin:0 0 4px; color:var(--ink); letter-spacing:-.01em; }
.diff-head-text p{ margin:0; font-size:clamp(.85rem, .8rem + .12vw, .98rem); color:var(--olive); line-height:1.5; }
.diff-close-btn{
  flex:none; width:34px; height:34px; border-radius:50%; border:1px solid var(--line);
  background:rgba(255,255,255,.6); color:var(--olive); font-size:1rem; line-height:1;
  display:flex; align-items:center; justify-content:center; cursor:pointer;
  transition:background .18s ease, color .18s ease, transform .18s ease, border-color .18s ease;
  margin-left:auto;
}
.diff-close-btn:hover{ background:rgba(180,83,65,.14); color:var(--danger); border-color:rgba(180,83,65,.3); transform:rotate(90deg); }
.diff-close-btn:focus-visible{ outline:2px solid var(--gold); outline-offset:2px; }

/* Pastilles de résumé — cliquables pour filtrer les lignes par type. */
.diff-stat{ cursor:pointer; border:1.5px solid transparent; user-select:none; }
.diff-stat:hover{ filter:brightness(.96); }
.diff-stat.is-off{ opacity:.38; filter:grayscale(.4); }
.diff-stats-hint{ width:100%; font-size:.72rem; color:var(--olive); opacity:.75; margin:2px 0 0; }

.diff-section{ scroll-margin-top:12px; }
.diff-section-title{ position:relative; }
.diff-section-undo{
  flex:none; font-family:var(--font-body); font-weight:600; font-size:.7rem;
  color:var(--olive); background:none; border:1px solid var(--line); border-radius:999px;
  padding:3px 9px; cursor:pointer; transition:background .16s ease, color .16s ease, border-color .16s ease;
  display:inline-flex; align-items:center; gap:4px;
}
.diff-section-title .diff-section-count + .diff-section-undo{ margin-left:6px; }
.diff-section-undo:hover{ background:rgba(180,83,65,.12); color:var(--danger); border-color:rgba(180,83,65,.28); }

.diff-row{ position:relative; padding-right:44px; }
.diff-row-undo{
  position:absolute; top:8px; right:8px; flex:none; width:26px; height:26px; border-radius:50%;
  border:1px solid var(--line); background:rgba(255,255,255,.7); color:var(--olive);
  font-size:.9rem; line-height:1; display:flex; align-items:center; justify-content:center;
  cursor:pointer; opacity:0; transform:scale(.85);
  transition:opacity .16s ease, transform .16s ease, background .16s ease, color .16s ease, border-color .16s ease;
}
.diff-row:hover .diff-row-undo, .diff-row-undo:focus-visible{ opacity:1; transform:scale(1); }
.diff-row-undo:hover{ background:rgba(180,83,65,.14); color:var(--danger); border-color:rgba(180,83,65,.3); }
@media(hover:none){ .diff-row-undo{ opacity:1; transform:scale(1); background:rgba(255,255,255,.85); } }
.diff-row.is-reverting{ animation:diffRowOut .28s ease both; }
@keyframes diffRowOut{ to{ opacity:0; transform:translateX(10px) scale(.97); max-height:0; margin:0; padding-top:0; padding-bottom:0; } }

/* ------------------------- Comparaison photo ------------------------- */
.diff-row.is-photo{ flex-direction:column; align-items:stretch; padding:14px 44px 14px 14px; }
.diff-row.is-photo .diff-row-badge{ position:absolute; top:14px; left:14px; }
.diff-photo-row{ padding-left:30px; }
.diff-photo-label{ margin:0 0 10px; font-size:.85rem; color:var(--ink); }
.diff-photo-label strong{ font-weight:700; }
.diff-photo-replace-tag{
  display:inline-flex; align-items:center; gap:5px; font-size:.7rem; font-weight:700;
  color:#7a5f38; background:rgba(171,143,102,.2); padding:2px 9px; border-radius:999px; margin-left:8px;
  vertical-align:1px;
}
.diff-img-compare{ display:flex; align-items:center; gap:10px; }
.diff-img-box{
  flex:1; min-width:0; display:flex; flex-direction:column; align-items:center; gap:6px;
  padding:10px; border-radius:14px; background:rgba(var(--ink-rgb),.03); border:1px solid var(--line);
}
.diff-img-box img{
  width:100%; aspect-ratio:4/3; object-fit:cover; border-radius:10px; display:block;
  background:var(--paper-soft); box-shadow:0 2px 8px rgba(var(--ink-rgb),.12);
}
.diff-img-box.is-old{ border-color:rgba(180,83,65,.35); }
.diff-img-box.is-old img{ outline:2px solid rgba(180,83,65,.35); outline-offset:-2px; }
.diff-img-box.is-new{ border-color:rgba(95,122,79,.4); }
.diff-img-box.is-new img{ outline:2px solid rgba(95,122,79,.45); outline-offset:-2px; }
.diff-img-box.is-broken img{ display:none; }
.diff-img-box.is-broken::after{ content:"🖼️ introuvable"; font-size:.72rem; color:var(--danger); padding:20px 4px; }
.diff-img-box.is-empty{
  aspect-ratio:4/3; width:100%; display:flex; flex-direction:column; align-items:center; justify-content:center;
  gap:4px; color:var(--olive); border-style:dashed;
}
.diff-img-empty-icon{ font-size:1.3rem; opacity:.6; }
.diff-img-tag{
  font-size:.68rem; font-weight:800; letter-spacing:.03em; text-transform:uppercase; padding:2px 9px; border-radius:999px;
}
.diff-img-box.is-old .diff-img-tag{ background:rgba(180,83,65,.16); color:var(--danger); }
.diff-img-box.is-new .diff-img-tag{ background:rgba(95,122,79,.18); color:var(--ok); }
.diff-img-box.is-empty .diff-img-tag{ background:rgba(var(--ink-rgb),.08); color:var(--olive); }
.diff-img-name{ font-size:.72rem; color:var(--olive); word-break:break-all; text-align:center; line-height:1.3; }
.diff-img-arrow{ flex:none; font-size:1.15rem; opacity:.55; }
/* Ligne dont checkPhotoRenames() a confirmé (async) que c'est la même
   photo, juste renommée : atténue le contraste rouge/vert « avant/après »
   qui suggère un vrai changement visuel, et affiche la note dédiée. */
.diff-photo-row.is-samephoto .diff-img-box.is-old,
.diff-photo-row.is-samephoto .diff-img-box.is-new{ opacity:.75; }
.diff-photo-samenote{
  margin:10px 0 0; font-size:.78rem; color:#7a5f38; background:rgba(171,143,102,.14);
  border:1px solid var(--gold-light); border-radius:10px; padding:7px 11px;
}
.diff-photo-samenote code{ font-size:.85em; }
@media(max-width:520px){
  .diff-img-compare{ flex-direction:column; }
  .diff-img-arrow{ transform:rotate(90deg); }
  .diff-img-box img{ aspect-ratio:16/9; }
}
/* ---------------- Comparaison visuelle du cadrage (point focal) ---------------- */
/* Quand un changement de CADRAGE (pas de photo) est détecté, montre le
   déplacement du point directement sur la photo — plutôt que deux
   pourcentages bruts ("50% 30% → 62% 20%") à interpréter mentalement.
   Une carte-repère très petite situe les deux points (avant en rouge,
   après en doré) sur la photo entière, et deux minuscules aperçus
   "avant/après" montrent le recadrage réel obtenu, au ratio exact du
   format concerné (mobile ou grand écran) — cohérent visuellement avec
   le sélecteur de cadrage lui-même. */
.diff-cadrage-compare{ position:relative; display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
/* Sonde invisible : un <img> cachée (mais bien chargée par le navigateur,
   contrairement à une simple image de fond CSS qui ne déclenche aucun
   évènement en cas d'échec) sert uniquement à détecter une photo
   manquante/introuvable côté serveur et à basculer tout le bloc en
   ".is-broken" — voir diffCadrageCompareHtml(). */
.diff-cadrage-probe{ display:none; }
.diff-cadrage-map{
  position:relative; flex:none; width:72px; aspect-ratio:1/1; border-radius:10px; overflow:hidden;
  background:var(--paper-soft) no-repeat center/cover; border:1px solid rgba(var(--ink-rgb),.16);
  box-shadow:0 2px 8px rgba(var(--ink-rgb),.1), inset 0 0 0 1px rgba(255,255,255,.25);
}
.diff-cadrage-dot{
  position:absolute; width:10px; height:10px; border-radius:50%; border:2px solid #fff;
  transform:translate(-50%,-50%); box-shadow:0 1px 3px rgba(0,0,0,.45);
}
.diff-cadrage-dot.is-old{ background:var(--danger); z-index:1; }
.diff-cadrage-dot.is-new{ background:var(--ok); z-index:2; }
.diff-cadrage-crops{ display:flex; align-items:center; gap:6px; }
.diff-cadrage-crop{
  position:relative; width:52px; border-radius:7px; overflow:hidden;
  background:var(--paper-soft) no-repeat center/cover; border:1px solid rgba(var(--ink-rgb),.16);
  box-shadow:0 1px 5px rgba(var(--ink-rgb),.14), inset 0 0 0 1px rgba(255,255,255,.25);
}
.diff-cadrage-crop.is-mobile{ aspect-ratio:8/5; }
.diff-cadrage-crop.is-desktop{ aspect-ratio:31/60; }
.diff-cadrage-crop-tag{
  position:absolute; bottom:2px; left:2px; font-size:.56rem; font-weight:800; letter-spacing:.02em;
  text-transform:uppercase; padding:1px 5px; border-radius:999px; color:#fff;
  text-shadow:0 1px 2px rgba(0,0,0,.4);
}
.diff-cadrage-crop-tag.is-old{ background:rgba(180,83,65,.85); }
.diff-cadrage-crop-tag.is-new{ background:rgba(95,122,79,.88); }
.diff-cadrage-crop-arrow{ flex:none; font-size:.9rem; opacity:.5; }
/* État de repli si la photo référencée par le cours est introuvable sur
   le serveur (supprimée, renommée manuellement…) : mieux vaut le dire
   clairement, comme pour .diff-img-box.is-broken, plutôt que d'afficher
   des cadres vides qui se fondent dans le fond de page et donnent
   l'impression d'un bug d'affichage. */
.diff-cadrage-broken-note{
  display:none; align-items:center; gap:6px; font-size:.78rem; color:var(--danger);
  background:rgba(180,83,65,.12); border:1px solid rgba(180,83,65,.3); border-radius:8px;
  padding:7px 11px; flex:1 1 220px;
}
.diff-cadrage-compare.is-broken .diff-cadrage-map,
.diff-cadrage-compare.is-broken .diff-cadrage-crops{ display:none; }
.diff-cadrage-compare.is-broken .diff-cadrage-broken-note{ display:flex; }
@media(max-width:520px){
  .diff-cadrage-map{ width:58px; }
  .diff-cadrage-crop{ width:44px; }
}

.diff-all-undone{
  text-align:center; padding:36px 20px 26px; color:var(--ok); font-size:.94rem;
  animation:diffPop .4s cubic-bezier(.22,1.4,.36,1) both;
}
.diff-all-undone-icon{ font-size:2.2rem; margin-bottom:10px; }

.diff-stats{ display:flex; flex-wrap:wrap; gap:8px; margin-top:16px; }
/* Résumé "quelles sections" (ex. "Thème, Galerie photo, Présentation")
   sous les pastilles de comptage — se lit avant même de faire défiler
   le détail plus bas. */
.diff-sections-summary{ margin:8px 0 0; font-size:.82rem; color:var(--olive); line-height:1.5; }
.diff-stat{
  display:inline-flex; align-items:center; gap:6px;
  padding:6px 12px; border-radius:999px; font-size:.8rem; font-weight:700;
  animation:diffPillIn .38s cubic-bezier(.34,1.56,.64,1) both;
}
.diff-stat:nth-child(1){ animation-delay:.05s; }
.diff-stat:nth-child(2){ animation-delay:.12s; }
.diff-stat:nth-child(3){ animation-delay:.19s; }
.diff-stat.is-added{ background:rgba(95,122,79,.16); color:var(--ok); }
.diff-stat.is-removed{ background:rgba(180,83,65,.14); color:var(--danger); }
.diff-stat.is-modified{ background:rgba(171,143,102,.2); color:#7a5f38; }

.diff-body{ flex:1; overflow-y:auto; -webkit-overflow-scrolling:touch; padding:6px 24px 20px; }
@media(min-width:1440px){ .diff-body{ padding-left:28px; padding-right:28px; } }
@media(min-width:2200px){ .diff-body{ padding-left:34px; padding-right:34px; } }

.diff-section{
  margin-top:20px; padding:14px 14px 8px; border-radius:16px;
  background:rgba(var(--ink-rgb),.025); border:1px solid var(--line);
}
.diff-section:first-child{ margin-top:16px; }
.diff-section-title{
  display:flex; align-items:center; gap:8px;
  font-family:var(--font-display); font-weight:700; font-size:clamp(.92rem,.85rem + .12vw,1.05rem);
  color:var(--ink); margin:0 0 10px; letter-spacing:-.005em;
}
.diff-section-title .diff-section-icon{ font-size:1.05em; line-height:1; }
.diff-section-title .diff-section-count{
  margin-left:auto; font-family:var(--font-body); font-weight:600; font-size:.72rem;
  color:var(--pine-dark); background:rgba(var(--pine-dark-rgb),.12); padding:2px 8px; border-radius:999px;
}

.diff-row{
  display:flex; gap:10px; align-items:flex-start;
  padding:10px 12px; border-radius:11px; margin-bottom:6px;
  font-size:clamp(.85rem,.8rem + .1vw,.95rem); line-height:1.55;
  border-left:3px solid transparent;
  animation:diffRowIn .32s ease both;
}
.diff-row.added{ background:rgba(95,122,79,.11); border-left-color:var(--ok); }
.diff-row.removed{ background:rgba(180,83,65,.09); border-left-color:var(--danger); }
.diff-row.modified{ background:rgba(171,143,102,.14); border-left-color:var(--gold); }
.diff-row-badge{
  flex:none; font-weight:800; width:20px; height:20px; border-radius:50%;
  display:flex; align-items:center; justify-content:center; font-size:.78rem;
  margin-top:1px;
}
.diff-row.added .diff-row-badge{ color:#fff; background:var(--ok); }
.diff-row.removed .diff-row-badge{ color:#fff; background:var(--danger); }
.diff-row.modified .diff-row-badge{ color:#fff; background:var(--gold); }
.diff-row-text{ color:var(--ink); word-break:break-word; }
.diff-row-text strong{ font-weight:700; }
.diff-old{ color:var(--danger); text-decoration:line-through; opacity:.75; }
.diff-new{ color:var(--ok); font-weight:700; }
.diff-arrow{ opacity:.5; margin:0 2px; }
/* Diff mot-à-mot (voir wordDiffHtml) : rendu façon "suivi des
   modifications" d'un traitement de texte — le texte final complet,
   jamais tronqué, avec les mots retirés barrés et les mots ajoutés
   soulignés, plutôt que deux paragraphes bruts à comparer soi-même. */
.diff-word-block{
  display:block; margin-top:4px; padding:10px 12px; border-radius:10px;
  background:var(--paper-soft); font-size:.9rem; line-height:1.6; color:var(--ink);
  word-break:break-word; white-space:pre-wrap;
}
.diff-word-del{
  color:var(--danger); text-decoration:line-through; text-decoration-thickness:1.5px;
  background:rgba(180,83,65,.1); border-radius:3px; padding:0 1px;
}
.diff-word-ins{
  color:var(--ok); text-decoration:none; font-weight:700;
  background:rgba(95,122,79,.14); border-radius:3px; padding:0 1px;
}
.diff-word-empty{ color:var(--olive); font-style:italic; }
.diff-swatch{
  display:inline-block; width:14px; height:14px; border-radius:5px;
  vertical-align:-3px; border:1px solid var(--line); margin:0 3px;
  box-shadow:0 1px 2px rgba(var(--ink-rgb),.15);
}

.diff-empty{
  text-align:center; padding:44px 20px 30px; color:var(--olive); font-size:.94rem;
}
.diff-empty-icon{ font-size:2.4rem; margin-bottom:12px; animation:diffPop .5s cubic-bezier(.22,1.4,.36,1) both; }

.diff-foot{
  padding:16px 24px calc(18px + env(safe-area-inset-bottom));
  border-top:1px solid var(--line); flex:none; background:var(--paper);
}
.diff-foot .diff-count{ font-size:.82rem; color:var(--olive); text-align:center; margin:0 0 12px; }
.diff-actions{ display:flex; gap:10px; }
.diff-actions .btn{ flex:1; min-width:0; white-space:normal; text-align:center; line-height:1.25; }
@media(min-width:1440px){
  .diff-foot{ padding-left:28px; padding-right:28px; }
  .diff-actions .btn{ font-size:1.02rem; padding-top:14px; padding-bottom:14px; }
}
@media(min-width:2200px){
  .diff-foot{ padding-left:34px; padding-right:34px; }
}

/* ===================== MODALE « PHOTOS MANQUANTES » ===================== */
.pm-dropzone{
  border:2px dashed var(--line); border-radius:16px; padding:22px 16px; text-align:center;
  cursor:pointer; transition:border-color .2s ease, background .2s ease; margin-bottom:18px;
}
.pm-dropzone:hover, .pm-dropzone:focus-visible, .pm-dropzone.is-drag{
  border-color:var(--gold); background:rgba(var(--pine-dark-rgb),.06); outline:none;
}
.pm-dropzone-icon{ font-size:1.6rem; margin-bottom:6px; }
.pm-dropzone-text{ font-size:.86rem; color:var(--olive); line-height:1.5; }
.pm-dropzone-text strong{ color:var(--ink); }

.pm-slot{
  border:1px solid var(--line); border-radius:14px; padding:14px; margin-bottom:12px;
  display:flex; gap:12px; align-items:flex-start;
}
.pm-slot.is-resolved{ border-color:rgba(95,122,79,.4); background:rgba(95,122,79,.06); }
.pm-slot.is-pending{ border-color:rgba(171,143,102,.5); background:rgba(171,143,102,.08); }
.pm-slot-status{
  flex:none; width:30px; height:30px; border-radius:50%;
  display:flex; align-items:center; justify-content:center; font-size:1rem;
  background:rgba(var(--ink-rgb),.06); color:var(--olive);
}
.pm-slot.is-resolved .pm-slot-status{ background:var(--ok); color:#fff; }
.pm-slot.is-pending .pm-slot-status{ background:var(--gold); color:#fff; }
.pm-slot-body{ flex:1; min-width:0; }
.pm-slot-path{ font-weight:700; font-size:.92rem; color:var(--ink); word-break:break-word; }
.pm-slot-refs{ font-size:.78rem; color:var(--pine-dark); margin-top:2px; }
.pm-slot-note{ font-size:.85rem; margin-top:8px; line-height:1.5; }
.pm-slot-note.is-warn{ color:#7a5f38; }
.pm-slot-note.is-ok{ color:var(--ok); }
.pm-slot-actions{ display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
.pm-slot-actions .btn{ font-size:.8rem; padding:7px 12px; }
.pm-slot-file-label{
  display:inline-flex; align-items:center; gap:6px; font-size:.8rem;
  padding:7px 12px; border-radius:999px; border:1px solid var(--line);
  cursor:pointer; color:var(--ink); background:#fff;
}
.pm-slot-file-label:hover{ border-color:var(--gold); }
/* ---------------------------------------------------------------------
   Vignettes de prévisualisation dans les emplacements photo — remplace
   le rond d'état par un aperçu réel du fichier dès qu'il est choisi,
   avec un petit badge de statut superposé (✓ / spinner / ?).
   --------------------------------------------------------------------- */
.pm-slot-visual{ position:relative; flex:none; width:52px; height:52px; }
.pm-slot-thumb{
  width:52px; height:52px; border-radius:12px; object-fit:cover; border:1px solid var(--line);
  background:var(--paper-soft); display:block;
  animation:pmThumbIn .35s cubic-bezier(.34,1.56,.64,1) both;
}
.pm-slot-thumb.is-broken{ object-fit:contain; padding:8px; opacity:.75; }
@keyframes pmThumbIn{ from{ transform:scale(.7); opacity:0; } to{ transform:scale(1); opacity:1; } }
.pm-slot-visual .pm-slot-status{
  position:absolute; right:-6px; bottom:-6px; width:24px; height:24px; font-size:.8rem;
  border:2px solid var(--paper);
}
.pm-slot:not(.has-thumb) .pm-slot-visual .pm-slot-status{ position:static; border:none; width:44px; height:44px; font-size:1.05rem; }
.pm-slot-status.is-pop{ animation:pmStatusPop .5s cubic-bezier(.34,1.56,.64,1) both; }
@keyframes pmStatusPop{
  0%{ transform:scale(.3) rotate(-30deg); opacity:0; }
  60%{ transform:scale(1.25) rotate(6deg); opacity:1; }
  100%{ transform:scale(1) rotate(0); }
}
.pm-slot-filesize{ font-size:.74rem; color:var(--pine-dark); margin-top:1px; }
.pm-slot-note.is-error{ color:var(--danger); }
.pm-slot-rename-arrow{ color:var(--pine-dark); font-weight:400; }
.pm-slot-path-final{ color:var(--ok); }

/* Barre de progression globale pendant l'envoi des photos manquantes */
.pm-progress{ margin-bottom:12px; }
.pm-progress-track{ height:8px; border-radius:999px; background:var(--paper-soft); overflow:hidden; }
.pm-progress-fill{
  height:100%; width:0%; border-radius:999px;
  background:linear-gradient(90deg, var(--gold-light), var(--gold));
  transition:width .35s cubic-bezier(.34,1.56,.64,1);
}
.pm-progress-label{ margin-top:6px; font-size:.8rem; color:var(--olive); text-align:center; }

.pm-unmatched-item{
  display:flex; align-items:center; justify-content:space-between; gap:10px;
  padding:8px 12px; border:1px solid var(--line); border-radius:10px; margin-bottom:6px; font-size:.85rem;
}
.pm-unmatched-item select{ font-family:inherit; font-size:.8rem; padding:5px 8px; border-radius:8px; border:1px solid var(--line); }

/* ===================== ENVOI LIBRE DE PHOTOS (panneau « 📷 Envoyer des photos sur le serveur ») ===================== */
.pu-dropzone{
  border:2px dashed var(--line); border-radius:16px; padding:28px 16px; text-align:center;
  cursor:pointer; transition:border-color .2s ease, background .2s ease, transform .15s ease; position:relative;
}
.pu-dropzone:hover, .pu-dropzone:focus-visible{ border-color:var(--gold); background:rgba(var(--pine-dark-rgb),.06); outline:none; }
.pu-dropzone.is-drag{ border-color:var(--gold); background:rgba(171,143,102,.14); transform:scale(1.01); }
.pu-dropzone-icon{ font-size:1.9rem; margin-bottom:8px; }
.pu-dropzone-text strong{ display:block; color:var(--ink); font-size:.98rem; margin-bottom:2px; }
.pu-dropzone-text span{ font-size:.82rem; color:var(--olive); }

.pu-grid{
  display:grid; grid-template-columns:repeat(auto-fill, minmax(150px, 1fr)); gap:14px;
  margin-top:16px;
}
.pu-card{
  border:1px solid var(--line); border-radius:14px; overflow:hidden; background:#fff;
  display:flex; flex-direction:column; animation:puCardIn .35s cubic-bezier(.34,1.56,.64,1) both;
  transition:border-color .2s ease, box-shadow .2s ease;
}
.pu-card:hover{ border-color:var(--gold); box-shadow:0 6px 18px rgba(61,53,44,.08); }
@keyframes puCardIn{ from{ transform:scale(.85); opacity:0; } to{ transform:scale(1); opacity:1; } }
.pu-card.is-done{ border-color:rgba(95,122,79,.4); background:rgba(95,122,79,.06); }
.pu-card.is-error, .pu-card.is-blocked{ border-color:rgba(180,83,65,.4); background:rgba(180,83,65,.06); }
.pu-card.is-uploading{ border-color:var(--gold); background:rgba(171,143,102,.08); }
.pu-card-thumb-wrap{
  position:relative; width:100%; aspect-ratio:1/1; background:var(--paper-soft); overflow:hidden;
}
.pu-card-thumb{ width:100%; height:100%; object-fit:cover; display:block; }
.pu-card-thumb.is-broken{ object-fit:contain; padding:16px; opacity:.75; }
.pu-card-remove{
  position:absolute; top:6px; right:6px; width:26px; height:26px; border-radius:50%; border:none;
  background:rgba(61,53,44,.55); color:#fff; font-size:.85rem; line-height:1; cursor:pointer;
  display:flex; align-items:center; justify-content:center; transition:background .15s ease, transform .15s ease;
}
.pu-card-remove:hover{ background:rgba(180,83,65,.85); transform:scale(1.08); }
.pu-card-status{
  position:absolute; left:6px; bottom:6px; width:24px; height:24px; border-radius:50%;
  display:flex; align-items:center; justify-content:center; font-size:.78rem; color:#fff;
  background:rgba(61,53,44,.55); border:2px solid rgba(255,255,255,.85);
}
.pu-card.is-done .pu-card-status{ background:var(--ok); }
.pu-card.is-error .pu-card-status{ background:var(--danger); }
.pu-card-body{ padding:10px; display:flex; flex-direction:column; gap:5px; }
.pu-card-name-row{ display:flex; align-items:center; gap:2px; }
.pu-card-name-input{
  flex:1; min-width:0; font-family:inherit; font-size:.8rem; padding:5px 6px; border-radius:7px;
  border:1px solid var(--line); background:var(--paper); color:var(--ink);
}
.pu-card-name-input:focus{ outline:2px solid var(--gold); outline-offset:1px; border-color:transparent; }
.pu-card-ext{ font-size:.8rem; color:var(--pine-dark); flex:none; }
.pu-card-meta{ font-size:.72rem; color:var(--pine-dark); }
.pu-card-preview-name{ font-size:.7rem; color:var(--olive); word-break:break-all; line-height:1.4; }
.pu-card-preview-name strong{ color:var(--ink); font-weight:600; }
.pu-card-msg{ font-size:.72rem; margin-top:2px; line-height:1.4; }
.pu-card.is-done .pu-card-msg{ color:var(--ok); }
.pu-card.is-error .pu-card-msg, .pu-card.is-blocked .pu-card-msg{ color:var(--danger); }
.pu-empty-hint{ font-size:.8rem; color:var(--pine-dark); margin-top:10px; text-align:center; }
@media(min-width:640px){
  .pu-grid{ grid-template-columns:repeat(auto-fill, minmax(160px, 1fr)); }
}

/* Sélecteur de dossier (Images principales / Galerie) du panneau d'envoi libre */
.pu-bucket-toggle{ display:flex; gap:8px; margin-bottom:14px; flex-wrap:wrap; }
.pu-bucket-opt{
  display:flex; align-items:center; gap:6px; padding:8px 14px; border:1.5px solid var(--line);
  border-radius:999px; font-size:.83rem; cursor:pointer; background:#fff; transition:border-color .15s ease, background .15s ease;
}
.pu-bucket-opt:has(input:checked){ border-color:var(--gold); background:rgba(171,143,102,.12); font-weight:600; }
.pu-bucket-opt input{ accent-color:var(--gold); }

/* Dropzone compacte réutilisée dans la Galerie photo pour l'ajout multiple */
.pu-dropzone-compact{ padding:18px 16px; }
.pu-dropzone-compact .pu-dropzone-icon{ font-size:1.5rem; margin-bottom:4px; }

/* ===================== ANCIENNES VERSIONS (panneau « 🗂 Anciennes versions ») ===================== */
.bkp-toolbar{
  display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px;
  margin-bottom:14px; padding:10px 12px; border:1px solid var(--line); border-radius:12px; background:var(--paper-soft);
}
.bkp-toolbar-group{ display:flex; flex-wrap:wrap; gap:8px; }
.bkp-grid{
  display:grid; grid-template-columns:1fr; gap:12px;
}
@media(min-width:720px){
  .bkp-grid{ grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); }
}
.bkp-card{
  border:1px solid var(--line); border-radius:14px; background:#fff; padding:14px;
  display:flex; flex-direction:column; gap:10px;
  animation:puCardIn .35s cubic-bezier(.34,1.56,.64,1) both;
  transition:border-color .2s ease, box-shadow .2s ease;
}
.bkp-card:hover{ border-color:var(--gold); box-shadow:0 6px 18px rgba(61,53,44,.08); }
.bkp-card.is-selected{ border-color:var(--gold); background:rgba(171,143,102,.07); }
.bkp-card.is-removing{ opacity:.4; pointer-events:none; transform:scale(.98); }
.bkp-card-top{ display:flex; align-items:flex-start; gap:10px; }
.bkp-check{ display:flex; align-items:center; padding-top:2px; flex:none; cursor:pointer; }
.bkp-check input{ width:18px; height:18px; accent-color:var(--gold); cursor:pointer; }
.bkp-card-info{ flex:1; min-width:0; }
.bkp-card-title{ display:flex; align-items:center; flex-wrap:wrap; gap:7px; font-weight:700; font-size:.94rem; color:var(--ink); }
.bkp-badge-new{
  font-size:.66rem; font-weight:700; letter-spacing:.03em; text-transform:uppercase;
  color:#fff; background:var(--ok); border-radius:999px; padding:2px 8px; line-height:1.5;
}
.bkp-card-meta{ font-size:.78rem; color:var(--pine-dark); margin-top:2px; }
.bkp-quick-delete{
  flex:none; width:32px; height:32px; border-radius:50%; border:1px solid var(--line); background:transparent;
  color:var(--danger); font-size:.9rem; cursor:pointer; display:flex; align-items:center; justify-content:center;
  transition:background .15s ease, border-color .15s ease, transform .15s ease;
}
.bkp-quick-delete:hover{ background:rgba(180,83,65,.1); border-color:var(--danger-light); transform:scale(1.06); }
.bkp-card-summary{ display:flex; flex-wrap:wrap; align-items:center; gap:6px; min-height:26px; font-size:.82rem; color:var(--olive); }
.bkp-diff-sections{ flex-basis:100%; font-size:.78rem; color:var(--olive); margin-top:2px; }
.bkp-diff-pill.is-none{
  font-size:.76rem; color:var(--pine-dark); background:var(--paper-soft); border-radius:999px; padding:3px 10px;
}
.bkp-diff-pill.is-error{
  font-size:.76rem; color:var(--danger); background:var(--danger-light); border-radius:999px; padding:3px 10px;
}
.bkp-card-actions{ display:flex; }
.bkp-card-actions .btn{ width:100%; }
</style>
</head>
<body>

<div id="loadingOverlay" class="loading-overlay">
  <div class="paw-loader" aria-hidden="true"><span>🐾</span><span>🐾</span><span>🐾</span></div>
  <div style="font-family:var(--font-body); color:var(--olive); font-size:.9rem;">Chargement de content.json…</div>
</div>

<div id="toast" class="toast"></div>

<header class="admin-header">
  <div class="container row">
    <div class="admin-brand">
      <div class="admin-brand-mark">M</div>
      <div>
        <div class="admin-brand-title">Momox Dogs</div>
        <div class="admin-brand-sub">Édition du site <span class="admin-version" title="Version de cette interface d'admin">· v<?= htmlspecialchars(MOMOX_ADMIN_VERSION, ENT_QUOTES, 'UTF-8') ?></span></div>
      </div>
    </div>
    <button class="btn btn-outline btn-sm" id="previewBtn" type="button" style="display:none;">👁 Aperçu du site</button>
    <button class="btn btn-outline btn-sm" id="guideBtn" type="button">📘 Guide complet</button>
    <a class="btn btn-outline btn-sm" href="admin.php?logout=1" id="logoutLink" style="text-decoration:none; display:inline-flex; align-items:center;">🔒 Déconnexion</a>
    <div id="autosaveWarn" class="status-pill warn" style="display:none;" title="La sauvegarde automatique locale ne fonctionne pas sur cet appareil (navigation privée ?). Télécharge ton fichier content.json régulièrement pour ne rien perdre.">Sauvegarde locale indisponible</div>
    <div id="statusPill" class="status-pill saved">À jour</div>
  </div>
</header>

<main id="appMain" class="container" style="display:none;">
  <div class="intro">
    <h1>Mettre à jour le site</h1>
    <p>Modifie les textes, les prix, l'ordre des cours ou les informations de contact ci-dessous, puis clique sur « 🚀 Publier en ligne » en bas de page — les changements partent directement sur le serveur, sans manipulation FTP. Le bouton « ⬇ Télécharger content.json » reste disponible en secours.</p>
    <p class="desc">Les champs marqués d'un <strong>*</strong> sont obligatoires. Pour les images, indique uniquement le nom du fichier (ex. <code>chien.jpg</code>) : le dossier est ajouté automatiquement.</p>
    <div class="panels-toolbar">
      <button class="btn btn-outline btn-sm" id="expandAllBtn" type="button">▾ Tout déplier</button>
      <button class="btn btn-outline btn-sm" id="collapseAllBtn" type="button">▴ Tout replier</button>
      <span id="globalDiffIndicator" class="status-pill" style="display:none;" title="Voir le détail de tous les changements en attente" role="button" tabindex="0"></span>
    </div>
  </div>

  <!-- ============================= VISIBILITÉ ============================= -->
  <section class="panel" data-panel data-diff-section="visibility">
    <div class="panel-head" data-toggle>
      <div class="panel-head-text"><h2>👁 Visibilité du site</h2><span class="hint">Masque temporairement des blocs entiers — rien n'est supprimé, tout reste réactivable en un clic</span></div>
      <div class="panel-head-right">
        <button class="btn btn-icon btn-outline panel-restore-btn" type="button" data-restore-section="visibility" style="display:none;" title="Restaurer cette section aux valeurs actuellement en ligne"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg></button>
        <span class="panel-diff-badge" data-diff-badge style="display:none;"></span>
        <svg class="panel-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 9l6 6 6-6"/></svg>
      </div>
    </div>
    <div class="panel-body">
      <div class="desc" style="margin-bottom:14px;">Désactive un interrupteur pour cacher l'élément correspondant sur le site — son contenu (texte, lien, ville…) reste intact ailleurs sur cette page, prêt à être réaffiché.</div>
      <div class="visibility-grid" id="visibilityGrid"><!-- généré automatiquement --></div>
    </div>
  </section>

  <!-- ============================= CONTACT ============================= -->
  <section class="panel" data-panel data-diff-section="contact">
    <div class="panel-head" data-toggle>
      <div class="panel-head-text"><h2>📇 Coordonnées de contact</h2><span class="hint">Email, téléphone, zone d'intervention, réseaux sociaux</span></div>
      <div class="panel-head-right">
        <button class="btn btn-icon btn-outline panel-restore-btn" type="button" data-restore-section="contact" style="display:none;" title="Restaurer cette section aux valeurs actuellement en ligne"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg></button>
        <span class="panel-diff-badge" data-diff-badge style="display:none;"></span>
        <svg class="panel-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 9l6 6 6-6"/></svg>
      </div>
    </div>
    <div class="panel-body">
      <div class="grid-2">
        <div class="field"><label>Email *</label><input type="email" data-bind="contact.email" spellcheck="false"></div>
        <div class="field"><label>Téléphone *</label><input type="tel" data-bind="contact.telephone" spellcheck="false"></div>
      </div>
      <div class="grid-2">
        <div class="field"><label>Ville *</label><input type="text" data-bind="contact.ville" spellcheck="true" lang="fr"></div>
        <div class="field"><label>Zone d'intervention (texte affiché) *</label><input type="text" data-bind="contact.zone" spellcheck="true" lang="fr"></div>
      </div>
      <div class="grid-2">
        <div class="field"><label>Lien Facebook</label><input type="url" data-bind="contact.facebook" spellcheck="false"></div>
        <div class="field"><label>Lien Instagram</label><input type="url" data-bind="contact.instagram" spellcheck="false"></div>
      </div>
      <div class="field-preview-wrap">
        <div class="field-preview-label">Aperçu simplifié du rendu</div>
        <div class="contact-mini-preview">
          <div class="cmp-location" id="cmpLocation"></div>
          <div class="cmp-cards">
            <div class="cmp-card"><span class="cmp-card-icon">✉️</span><span class="cmp-card-text" id="cmpEmail"></span></div>
            <div class="cmp-card"><span class="cmp-card-icon">📞</span><span class="cmp-card-text" id="cmpPhone"></span></div>
          </div>
          <div class="cmp-social" id="cmpSocial" style="display:none;">
            <span id="cmpFacebook" style="display:none;">Facebook</span>
            <span id="cmpInstagram" style="display:none;">Instagram</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================= ACCUEIL (HERO) ============================= -->
  <section class="panel" data-panel data-diff-section="hero">
    <div class="panel-head" data-toggle>
      <div class="panel-head-text"><h2>🏠 Page d'accueil</h2><span class="hint">Le grand titre affiché en haut du site</span></div>
      <div class="panel-head-right">
        <button class="btn btn-icon btn-outline panel-restore-btn" type="button" data-restore-section="hero" style="display:none;" title="Restaurer cette section aux valeurs actuellement en ligne"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg></button>
        <span class="panel-diff-badge" data-diff-badge style="display:none;"></span>
        <svg class="panel-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 9l6 6 6-6"/></svg>
      </div>
    </div>
    <div class="panel-body">
      <div class="field"><label>Badge (petit texte au-dessus du titre)</label><input type="text" data-bind="hero.badge" id="heroBadgeInput" maxlength="40" spellcheck="true" lang="fr"><div class="field-counter" id="heroBadgeCounter"></div><div class="desc">Laisser vide pour ne pas afficher de badge. Reste très court (majuscules, style « étiquette »).</div></div>
      <div class="field">
        <label>Titre principal *</label>
        <input type="text" data-bind="hero.titre" id="heroTitreInput" maxlength="90" spellcheck="true" lang="fr">
        <div class="field-counter" id="heroTitreCounter"></div>
        <div class="desc">Astuce : entoure un mot de <code>&lt;em&gt;</code>...<code>&lt;/em&gt;</code> pour l'afficher en italique doré, ex. « Une éducation &lt;em&gt;sur-mesure&lt;/em&gt; ».</div>
      </div>
      <div class="field"><label>Texte d'introduction *</label><textarea data-bind="hero.texte" spellcheck="true" lang="fr"></textarea></div>
      <div class="field-preview-wrap">
        <div class="field-preview-label">Aperçu simplifié du rendu</div>
        <div class="hero-mini-preview">
          <span class="hmp-badge" id="hmpBadge" style="display:none;"></span>
          <h3 class="hmp-title" id="hmpTitle"></h3>
          <p class="hmp-text" id="hmpText"></p>
          <span class="hmp-btn">Prendre rendez-vous</span>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================= PRESENTATION ============================= -->
  <section class="panel" data-panel data-diff-section="presentation">
    <div class="panel-head" data-toggle>
      <div class="panel-head-text"><h2>👋 Présentation</h2><span class="hint">« Bonjour, je suis Morgane »</span></div>
      <div class="panel-head-right">
        <button class="btn btn-icon btn-outline panel-restore-btn" type="button" data-restore-section="presentation" style="display:none;" title="Restaurer cette section aux valeurs actuellement en ligne"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg></button>
        <span class="panel-diff-badge" data-diff-badge style="display:none;"></span>
        <svg class="panel-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 9l6 6 6-6"/></svg>
      </div>
    </div>
    <div class="panel-body">
      <div class="field"><label>Titre *</label><input type="text" data-bind="presentation.titre" spellcheck="true" lang="fr"></div>
      <div class="field"><label>Premier paragraphe *</label><textarea data-bind="presentation.texte1" spellcheck="true" lang="fr"></textarea></div>
      <div class="field"><label>Second paragraphe</label><textarea data-bind="presentation.texte2" spellcheck="true" lang="fr"></textarea><div class="desc">Laisser vide pour ne pas afficher ce paragraphe.</div></div>
      <div class="field-preview-wrap">
        <div class="field-preview-label">Aperçu simplifié du rendu</div>
        <div class="pres-mini-preview">
          <h3 class="pmp-title" id="pmpTitle"></h3>
          <p class="pmp-text" id="pmpText1"></p>
          <p class="pmp-text" id="pmpText2" style="display:none;"></p>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================= POURQUOI ============================= -->
  <section class="panel" data-panel data-diff-section="pourquoi">
    <div class="panel-head" data-toggle>
      <div class="panel-head-text"><h2>💡 Section « Pourquoi »</h2><span class="hint">Pourquoi suivre des cours d'éducation canine</span></div>
      <div class="panel-head-right">
        <button class="btn btn-icon btn-outline panel-restore-btn" type="button" data-restore-section="pourquoi" style="display:none;" title="Restaurer cette section aux valeurs actuellement en ligne"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg></button>
        <span class="panel-diff-badge" data-diff-badge style="display:none;"></span>
        <svg class="panel-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 9l6 6 6-6"/></svg>
      </div>
    </div>
    <div class="panel-body">
      <div class="grid-2">
        <div class="field"><label>Eyebrow (petit texte au-dessus du titre)</label><input type="text" data-bind="pourquoi.eyebrow" spellcheck="true" lang="fr"><div class="desc">Laisser vide pour ne pas afficher cet eyebrow.</div></div>
        <div class="field"><label>Titre *</label><input type="text" data-bind="pourquoi.titre" spellcheck="true" lang="fr"></div>
      </div>
      <div class="field"><label>Premier paragraphe *</label><textarea data-bind="pourquoi.texte1" spellcheck="true" lang="fr"></textarea></div>
      <div class="field"><label>Second paragraphe</label><textarea data-bind="pourquoi.texte2" spellcheck="true" lang="fr"></textarea><div class="desc">Laisser vide pour ne pas afficher ce paragraphe.</div></div>
      <div class="field"><label>Citation mise en avant</label><textarea data-bind="pourquoi.citation" spellcheck="true" lang="fr"></textarea><div class="desc">Laisser vide pour ne pas afficher de citation.</div></div>
      <div class="field-preview-wrap">
        <div class="field-preview-label">Aperçu simplifié du rendu</div>
        <div class="why-mini-preview">
          <span class="wmp-eyebrow" id="wmpEyebrow" style="display:none;"></span>
          <h3 class="wmp-title" id="wmpTitle"></h3>
          <p class="wmp-text" id="wmpText1"></p>
          <p class="wmp-text" id="wmpText2" style="display:none;"></p>
          <blockquote class="wmp-quote" id="wmpQuote" style="display:none;"></blockquote>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================= ETIQUETTES ============================= -->
  <section class="panel" data-panel data-diff-section="tags">
    <div class="panel-head" data-toggle>
      <div class="panel-head-text"><h2>🏷️ Étiquettes</h2><span class="hint">Petits mots-clés affichés sur le site (ex. « Éducation canine »)</span></div>
      <div class="panel-head-right">
        <button class="btn btn-icon btn-outline panel-restore-btn" type="button" data-restore-section="tags" style="display:none;" title="Restaurer cette section aux valeurs actuellement en ligne"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg></button>
        <span class="panel-diff-badge" data-diff-badge style="display:none;"></span>
        <svg class="panel-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 9l6 6 6-6"/></svg>
      </div>
    </div>
    <div class="panel-body">
      <div id="tagList" class="tag-list"></div>
      <p class="reorder-hint" style="margin-bottom:14px;">↕ Glisse une étiquette pour la déplacer, ou utilise les flèches ↑ ↓ — l&rsquo;ordre choisi est celui affiché sur le site.</p>
      <div class="add-row">
        <input type="text" id="newTagInput" placeholder="Ajouter une étiquette…" spellcheck="true" lang="fr">
        <button class="btn btn-outline btn-sm" id="addTagBtn" type="button">Ajouter</button>
      </div>
      <div id="tagError" class="field-error"></div>
    </div>
  </section>

  <!-- ============================= COURS & TARIFS ============================= -->
  <section class="panel" data-panel data-diff-section="services">
    <div class="panel-head" data-toggle>
      <div class="panel-head-text"><h2>🐾 Cours &amp; tarifs</h2><span class="hint">Ajoute, modifie, réordonne ou supprime les prestations proposées</span></div>
      <div class="panel-head-right">
        <button class="btn btn-icon btn-outline panel-restore-btn" type="button" data-restore-section="services" style="display:none;" title="Restaurer cette section aux valeurs actuellement en ligne"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg></button>
        <span class="panel-diff-badge" data-diff-badge style="display:none;"></span>
        <svg class="panel-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 9l6 6 6-6"/></svg>
      </div>
    </div>
    <div class="panel-body">
      <div id="servicesList"></div>
      <button class="btn add-service-btn" id="addServiceBtn" type="button">+ Ajouter un cours / une prestation</button>
    </div>
  </section>

  <!-- ============================= IMAGES PRINCIPALES ============================= -->
  <section class="panel" data-panel data-diff-section="images">
    <div class="panel-head" data-toggle>
      <div class="panel-head-text"><h2>🖼️ Images principales</h2><span class="hint">Photo d'accueil, fond de la section contact, portrait</span></div>
      <div class="panel-head-right">
        <button class="btn btn-icon btn-outline panel-restore-btn" type="button" data-restore-section="images" style="display:none;" title="Restaurer cette section aux valeurs actuellement en ligne"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg></button>
        <span class="panel-diff-badge" data-diff-badge style="display:none;"></span>
        <svg class="panel-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 9l6 6 6-6"/></svg>
      </div>
    </div>
    <div class="panel-body" id="mainImagesBody"></div>
  </section>

  <!-- ============================= GALERIE ============================= -->
  <section class="panel" data-panel data-diff-section="galerie galerieTexte">
    <div class="panel-head" data-toggle>
      <div class="panel-head-text"><h2>📷 Galerie photo</h2><span class="hint">La section « Galerie » apparaît sur le site dès qu'une photo est ajoutée</span></div>
      <div class="panel-head-right">
        <button class="btn btn-icon btn-outline panel-restore-btn" type="button" data-restore-section="galerie galerieTexte" style="display:none;" title="Restaurer cette section aux valeurs actuellement en ligne"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg></button>
        <span class="panel-diff-badge" data-diff-badge style="display:none;"></span>
        <svg class="panel-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 9l6 6 6-6"/></svg>
      </div>
    </div>
    <div class="panel-body">
      <div class="field"><label>Titre affiché au-dessus de la galerie</label><input type="text" data-bind="galerieTexte.titre" spellcheck="true" lang="fr"><div class="desc">Laisser vide pour garder le titre par défaut (« Les chiens que j'accompagne. »).</div></div>

      <div class="field">
        <label>Type d'affichage</label>
        <div class="gallery-layout-picker" id="galleryLayoutPicker"></div>
      </div>

      <div class="gallery-live-preview-wrap">
        <div class="gallery-live-preview-label">Aperçu simplifié du rendu</div>
        <div class="gallery-live-preview" id="galleryLivePreview"></div>
      </div>

      <div id="galerieStatus" class="desc" style="margin-bottom:14px;"></div>

      <div class="desc" style="margin-bottom:10px;">Dépose une ou plusieurs photos ci-dessous pour les envoyer directement dans <code>images/galerie/</code> et les ajouter à la liste — le mot de passe admin est redemandé avant l'envoi. <strong>Si une photo du même nom existe déjà, l'envoi est refusé</strong> : renomme-la avant d'envoyer.</div>
      <div class="pu-dropzone pu-dropzone-compact" id="galerieDropZone" tabindex="0" role="button" aria-label="Déposer plusieurs photos pour les ajouter à la galerie">
        <input type="file" id="galerieDropInput" accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif" multiple style="position:absolute; width:1px; height:1px; opacity:0; pointer-events:none;">
        <div class="pu-dropzone-icon">📥</div>
        <div class="pu-dropzone-text">
          <strong>Glisse-dépose plusieurs photos ici</strong>
          <span>ou clique pour parcourir — elles seront ajoutées à la galerie après envoi</span>
        </div>
      </div>
      <div id="galerieUploadGrid" class="pu-grid"></div>
      <div id="galerieUploadEmptyHint" class="pu-empty-hint" style="display:none;">Aucune photo en attente d'envoi.</div>
      <button class="btn btn-outline btn-sm" id="galerieUploadBtn" type="button" style="margin-top:14px; display:none;">⬆ Envoyer et ajouter à la galerie</button>

      <div id="galerieList" style="margin-top:18px;"></div>
      <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:4px;">
        <button class="btn add-service-btn" id="addGalerieBtn" type="button">+ Ajouter une photo</button>
        <button class="btn btn-outline btn-sm" id="clearGalerieBtn" type="button" style="display:none;">🗑 Vider la galerie</button>
      </div>
    </div>
  </section>

  <!-- ============================= ENVOYER DES PHOTOS ============================= -->
  <section class="panel" data-panel>
    <div class="panel-head" data-toggle>
      <div><h2>📷 Envoyer des photos sur le serveur</h2><span class="hint">Ajoute directement une ou plusieurs photos dans le dossier images/ du site</span></div>
      <svg class="panel-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 9l6 6 6-6"/></svg>
    </div>
    <div class="panel-body">
      <div class="desc" style="margin-bottom:14px;">Choisis le dossier de destination, dépose une ou plusieurs photos ci-dessous (ou clique pour parcourir), vérifie les aperçus, renomme-les si besoin, puis clique sur « Envoyer » — le mot de passe admin est redemandé avant l'envoi, comme pour une publication. Une fois envoyée, une photo apparaît dans le dossier choisi — tu peux ensuite taper son nom exact dans les champs « Images principales » ou « Galerie photo » ci-dessus (ou utiliser directement 🖼️ pour en choisir une déjà en ligne, ou 📷 pour en envoyer une nouvelle directement depuis ces champs). <strong>Si une photo du même nom existe déjà sur le serveur, l'envoi est signalé et bloqué</strong> pour éviter d'écraser une image sans le vouloir — renomme le fichier ou supprime d'abord l'ancienne via « 🧹 Nettoyer les photos inutilisées ».</div>

      <div class="pu-bucket-toggle" role="radiogroup" aria-label="Dossier de destination">
        <label class="pu-bucket-opt"><input type="radio" name="puBucket" value="images" checked> 🖼️ Images principales (<code>images/</code>)</label>
        <label class="pu-bucket-opt"><input type="radio" name="puBucket" value="galerie"> 📷 Galerie (<code>images/galerie/</code>)</label>
      </div>

      <div class="pu-dropzone" id="photoDropZone" tabindex="0" role="button" aria-label="Choisir ou déposer des photos à envoyer">
        <input type="file" id="photoUploadInput" accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif" multiple style="position:absolute; width:1px; height:1px; opacity:0; pointer-events:none;">
        <div class="pu-dropzone-icon">📥</div>
        <div class="pu-dropzone-text">
          <strong>Glisse-dépose tes photos ici</strong>
          <span>ou clique pour parcourir — JPG, PNG, WEBP, GIF, plusieurs à la fois</span>
        </div>
      </div>

      <div id="photoUploadGrid" class="pu-grid"></div>
      <div id="photoUploadEmptyHint" class="pu-empty-hint" style="display:none;">Aucune photo sélectionnée pour le moment.</div>

      <button class="btn btn-outline btn-sm" id="photoUploadBtn" type="button" style="margin-top:14px;" disabled>⬆ Envoyer</button>
    </div>
  </section>

  <!-- ============================= ANCIENNES VERSIONS ============================= -->
  <section class="panel" data-panel data-lazy-panel="backups">
    <div class="panel-head" data-toggle>
      <div><h2>🗂 Anciennes versions</h2><span class="hint">Retrouve, compare et nettoie les sauvegardes de content.json</span></div>
      <svg class="panel-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 9l6 6 6-6"/></svg>
    </div>
    <div class="panel-body">
      <div class="desc" style="margin-bottom:14px;">Chaque publication garde automatiquement une copie de la version précédente. Le résumé sous chaque version indique déjà ce qui changerait par rapport au contenu actuel de l'éditeur — clique sur « Comparer & charger » pour voir le détail complet et la recharger. Rien n'est modifié sur le site tant que tu ne cliques pas ensuite sur « 🚀 Publier en ligne ».</div>

      <div class="bkp-toolbar" id="backupsToolbar" style="display:none;">
        <div class="bkp-toolbar-group">
          <button class="btn btn-outline btn-sm" id="selectAllBackupsBtn" type="button">Tout sélectionner</button>
          <button class="btn btn-outline btn-sm" id="selectNoneBackupsBtn" type="button">Tout désélectionner</button>
        </div>
        <button class="btn btn-danger btn-sm" id="deleteBackupsBtn" type="button" disabled>🗑 Supprimer la sélection</button>
      </div>

      <button class="btn btn-outline btn-sm" id="refreshBackupsBtn" type="button" style="margin-bottom:14px;">↻ Rafraîchir la liste</button>
      <div class="bkp-search" id="backupsSearchWrap" style="display:none;">
        <input type="search" id="backupsSearchInput" placeholder="Filtrer par date (ex. « hier », « mars », « 12 »)…" aria-label="Filtrer les anciennes versions par date" spellcheck="false">
      </div>
      <div id="backupsSearchEmpty" class="bkp-search-empty" style="display:none;">Aucune version ne correspond à ce filtre.</div>
      <div id="backupsList" class="bkp-grid"></div>
    </div>
  </section>

  <!-- ============================= NETTOYAGE DES PHOTOS ============================= -->
  <section class="panel" data-panel data-lazy-panel="unused">
    <div class="panel-head" data-toggle>
      <div><h2>🧹 Nettoyer les photos inutilisées</h2><span class="hint">Supprime du serveur les photos qui ne sont plus utilisées par le site</span></div>
      <svg class="panel-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 9l6 6 6-6"/></svg>
    </div>
    <div class="panel-body">
      <div class="desc" style="margin-bottom:14px;">Recherche les photos présentes dans <code>images/</code> et <code>images/galerie/</code> mais qui ne sont référencées nulle part dans le contenu actuellement en ligne. Les logos et icônes du site (<code>images/icons/</code>) ne sont jamais concernés. Si une ancienne sauvegarde fait encore référence à une photo supprimée, elle est automatiquement supprimée avec elle — impossible de restaurer une sauvegarde vers une photo qui n'existe plus.</div>
      <button class="btn btn-outline btn-sm" id="scanUnusedBtn" type="button">🔍 Rechercher les photos inutilisées</button>
      <div id="unusedResultsWrap" style="display:none; margin-top:16px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:10px; flex-wrap:wrap;">
          <div id="unusedSummary" class="desc" style="margin:0;"></div>
          <div style="display:flex; gap:8px;">
            <button class="btn btn-outline btn-sm" id="selectAllUnusedBtn" type="button">Tout sélectionner</button>
            <button class="btn btn-outline btn-sm" id="selectNoneUnusedBtn" type="button">Tout désélectionner</button>
          </div>
        </div>
        <div id="unusedList" style="display:flex; flex-direction:column; gap:10px;"></div>
        <button class="btn btn-danger" id="deleteUnusedBtn" type="button" style="margin-top:16px; width:100%;" disabled>🗑 Supprimer la sélection</button>
      </div>
      <div id="unusedEmptyMsg" class="desc" style="display:none; margin-top:14px;"></div>
    </div>
  </section>

  <!-- ============================= MENU DE NAVIGATION ============================= -->
  <section class="panel" data-panel data-diff-section="nav">
    <div class="panel-head" data-toggle>
      <div class="panel-head-text"><h2>🧭 Menu de navigation</h2><span class="hint">Le texte des liens en haut du site (l'ordre suit celui-ci)</span></div>
      <div class="panel-head-right">
        <button class="btn btn-icon btn-outline panel-restore-btn" type="button" data-restore-section="nav" style="display:none;" title="Restaurer cette section aux valeurs actuellement en ligne"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg></button>
        <span class="panel-diff-badge" data-diff-badge style="display:none;"></span>
        <svg class="panel-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 9l6 6 6-6"/></svg>
      </div>
    </div>
    <div class="panel-body">
      <div id="navList"></div>
      <div class="desc" style="margin-top:6px;">Le lien « Galerie » s'ajoute automatiquement à la fin du menu dès qu'une photo est ajoutée ci-dessus — pas besoin de le gérer ici.</div>
    </div>
  </section>

  <!-- ============================= COULEURS DU SITE ============================= -->
  <section class="panel" data-panel data-diff-section="theme">
    <div class="panel-head" data-toggle>
      <div class="panel-head-text"><h2>🎨 Couleurs du site</h2><span class="hint">Palette utilisée sur tout le site — à modifier avec précaution</span></div>
      <div class="panel-head-right">
        <button class="btn btn-icon btn-outline panel-restore-btn" type="button" data-restore-section="theme" style="display:none;" title="Restaurer cette section aux valeurs actuellement en ligne"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg></button>
        <span class="panel-diff-badge" data-diff-badge style="display:none;"></span>
        <svg class="panel-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 9l6 6 6-6"/></svg>
      </div>
    </div>
    <div class="panel-body">
      <div class="theme-intro">
        <p class="desc">9 couleurs suffisent à habiller tout le site. Survole ou modifie un champ ci-dessous : la zone concernée s'illumine avec une étiquette dans l'aperçu, et le reste s'assombrit légèrement pour que tu voies immédiatement où — et où d'autre — elle s'applique.</p>
      </div>

      <div id="themePresets" class="theme-presets"></div>

      <div class="theme-preview-card" id="themePreviewCard">
        <div class="tp-dots"><span></span><span></span><span></span></div>
        <div class="tp-header" data-role="paper" data-role-label="Fond général">
          <div class="tp-brand" data-role="ink" data-role-label="Texte principal">Momox <em data-role="gold" data-role-label="Accent doré">Dogs</em></div>
          <div class="tp-nav" data-role="olive" data-role-label="Texte secondaire">Présentation · Pourquoi · Cours</div>
        </div>
        <div class="tp-hero" data-role="pineDark" data-role-label="Fonds foncés">
          <div class="tp-eyebrow" data-role="goldLight" data-role-label="Beige clair lumineux">ÉDUCATION CANINE</div>
          <div class="tp-title" data-role="cream" data-role-label="Texte clair">Une éducation <em data-role="gold" data-role-label="Accent doré">sur-mesure</em>, pas à pas.</div>
          <span class="tp-btn" data-role="gold" data-role-label="Accent doré">Prendre rendez-vous</span>
        </div>
        <div class="tp-section" data-role="paper" data-role-label="Fond général">
          <div class="tp-card" data-role="paperSoft" data-role-label="Fond des sections alternées">
            <span class="tp-dot" data-role="pine" data-role-label="Accent secondaire"></span>
            <div class="tp-card-title" data-role="ink" data-role-label="Texte principal">Cours individuel</div>
            <div class="tp-card-text" data-role="olive" data-role-label="Texte secondaire">Un exemple de paragraphe pour visualiser le texte courant.</div>
            <span class="tp-pill" data-role="pine" data-role-label="Accent secondaire">Accent</span>
          </div>
        </div>
        <div class="tp-alt-section" data-role="paperSoft" data-role-label="Fond des sections alternées">
          <div class="tp-alt-title" data-role="ink" data-role-label="Texte principal">Pourquoi Momox Dogs ?</div>
          <div class="tp-alt-text" data-role="olive" data-role-label="Texte secondaire">Ce même fond habille aussi une section entière — pas seulement les cartes.</div>
        </div>
        <div class="tp-footer" data-role="pineDark" data-role-label="Fonds foncés">
          <span data-role="cream" data-role-label="Texte clair">© Momox Dogs</span>
          <span class="tp-footer-link" data-role="gold" data-role-label="Accent doré">Contact</span>
        </div>
      </div>
      <div class="theme-preview-hint">Ceci est une maquette miniature — utilise « Aperçu du site » en haut de page pour voir le rendu réel et complet.</div>

      <div id="themeBody" style="margin-top:22px;"></div>
      <button class="btn btn-outline btn-sm" id="resetThemeBtn" type="button" style="margin-top:8px;">Réinitialiser aux couleurs d'origine</button>
    </div>
  </section>

  <!-- ============================= PIED DE PAGE ============================= -->
  <section class="panel" data-panel data-diff-section="footer">
    <div class="panel-head" data-toggle>
      <div class="panel-head-text"><h2>📄 Pied de page</h2><span class="hint">Copyright et petite phrase en bas du site</span></div>
      <div class="panel-head-right">
        <button class="btn btn-icon btn-outline panel-restore-btn" type="button" data-restore-section="footer" style="display:none;" title="Restaurer cette section aux valeurs actuellement en ligne"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg></button>
        <span class="panel-diff-badge" data-diff-badge style="display:none;"></span>
        <svg class="panel-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 9l6 6 6-6"/></svg>
      </div>
    </div>
    <div class="panel-body">
      <div class="field"><label>Copyright *</label><input type="text" data-bind="footer.copyright" spellcheck="true" lang="fr"></div>
      <div class="field"><label>Phrase de fin</label><input type="text" data-bind="footer.tagline" spellcheck="true" lang="fr"><div class="desc">Laisser vide pour ne pas afficher de phrase de fin.</div></div>
      <div class="field-preview-wrap">
        <div class="field-preview-label">Aperçu simplifié du rendu</div>
        <div class="footer-mini-preview">
          <span class="fmp-copy" id="fmpCopy"></span>
          <span class="fmp-tagline" id="fmpTagline" style="display:none;"></span>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================= SEO ============================= -->
  <section class="panel" data-panel data-diff-section="seo">
    <div class="panel-head" data-toggle>
      <div class="panel-head-text"><h2>🔍 Référencement (SEO)</h2><span class="hint">Titre et description utilisés par Google et les réseaux sociaux</span></div>
      <div class="panel-head-right">
        <button class="btn btn-icon btn-outline panel-restore-btn" type="button" data-restore-section="seo" style="display:none;" title="Restaurer cette section aux valeurs actuellement en ligne"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg></button>
        <span class="panel-diff-badge" data-diff-badge style="display:none;"></span>
        <svg class="panel-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 9l6 6 6-6"/></svg>
      </div>
    </div>
    <div class="panel-body">
      <div class="desc" style="margin-bottom:16px;">Ces réglages contrôlent comment le site apparaît dans les résultats Google, et à quoi ressemble un lien vers le site quand il est partagé (Facebook, Instagram, WhatsApp, SMS…).</div>

      <div class="field">
        <label>Titre du site *</label>
        <input type="text" data-bind="seo.siteTitle" id="seoTitleInput" spellcheck="true" lang="fr">
        <div class="seo-counter" id="seoTitleCounter"></div>
        <div class="desc">Apparaît en gras dans les résultats Google et dans l'onglet du navigateur. Vise 50 à 60 caractères pour éviter qu'il soit coupé.</div>
      </div>

      <div class="field">
        <label>Description *</label>
        <textarea data-bind="seo.description" id="seoDescInput" spellcheck="true" lang="fr"></textarea>
        <div class="seo-counter" id="seoDescCounter"></div>
        <div class="desc">Le texte affiché sous le titre dans Google, et repris quand le lien est partagé sur les réseaux sociaux ou WhatsApp. Vise 120 à 160 caractères.</div>
      </div>

      <div class="field">
        <label>Adresse du site (URL) *</label>
        <input type="url" data-bind="seo.siteUrl" id="seoUrlInput" spellcheck="false">
        <div class="desc">L'adresse complète du site en ligne (ex. https://momoxdogs.fr), sans slash final.</div>
      </div>

      <div id="seoShareImageWrap"></div>

      <div class="seo-previews">
        <div class="seo-preview-block">
          <div class="seo-preview-label">🔍 Aperçu dans Google</div>
          <div class="seo-google-preview">
            <div class="seo-g-site"><span class="seo-g-favicon">M</span><span id="seoGSite">votresite.fr</span></div>
            <div class="seo-g-title" id="seoGTitle">Titre du site</div>
            <div class="seo-g-desc" id="seoGDesc">Description du site…</div>
          </div>
        </div>
        <div class="seo-preview-block">
          <div class="seo-preview-label">📱 Aperçu de partage (Facebook, WhatsApp…)</div>
          <div class="seo-social-preview">
            <div class="seo-s-image" id="seoSImage"></div>
            <div class="seo-s-body">
              <div class="seo-s-domain" id="seoSDomain">VOTRESITE.FR</div>
              <div class="seo-s-title" id="seoSTitle">Titre du site</div>
              <div class="seo-s-desc" id="seoSDesc">Description du site…</div>
            </div>
          </div>
        </div>
      </div>
      <div class="theme-preview-hint">Ces deux aperçus sont approximatifs — Google et chaque réseau social ont leur propre mise en forme, qui peut légèrement varier.</div>
    </div>
  </section>

  <div style="height:8px;"></div>
</main>

<div id="emptyState" class="empty-state" style="display:none;">
  <div id="emptyStateError" class="empty-state-error" style="display:none;">
    <strong>Ce fichier n'a pas pu être utilisé</strong>
    <p id="emptyStateErrorText"></p>
  </div>
  <h2 id="emptyStateTitle">Impossible de charger content.json automatiquement</h2>
  <p id="emptyStateText">Si tu ouvres cette page directement depuis ton ordinateur (sans serveur web), le navigateur bloque le chargement automatique du fichier.<br>Choisis-le manuellement ci-dessous :</p>
  <label class="btn btn-primary file-input-label">
    Choisir content.json
    <input type="file" id="manualFileInput" accept="application/json,.json">
  </label>
</div>

<div class="actionbar" id="actionbar" style="display:none;">
  <div class="container">
    <div class="actionbar-row-top">
      <button class="btn btn-icon btn-outline" id="undoBtn" type="button" title="Rien à annuler" disabled>
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/></svg>
      </button>
      <button class="btn btn-icon btn-outline" id="redoBtn" type="button" title="Rien à rétablir" disabled>
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 14l5-5-5-5"/><path d="M20 9H9.5a5.5 5.5 0 0 0 0 11H13"/></svg>
      </button>
      <button class="btn btn-icon btn-outline" id="reloadFileBtn" type="button" title="Charger un fichier JSON (fonction technique)">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4"/><path d="M6 10l6-6 6 6"/><path d="M4 20h16"/></svg>
      </button>
      <input type="file" id="reloadFileInput" accept="application/json,.json" style="position:absolute; width:1px; height:1px; opacity:0; pointer-events:none;">
      <button class="btn btn-icon btn-outline" id="restoreAllBtn" type="button" title="Tout restaurer aux valeurs actuellement en ligne">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg>
      </button>
    </div>
    <button class="btn btn-primary actionbar-download" id="publishBtn" type="button">🚀 Publier en ligne</button>
    <button class="btn btn-outline actionbar-download" id="downloadBtn" type="button">⬇ Télécharger content.json (secours)</button>
  </div>
</div>

<div id="previewOverlay" class="preview-overlay" style="display:none;">
  <div class="preview-topbar">
    <div class="preview-topbar-left">
      <strong>Aperçu du site</strong>
      <span class="desc" id="previewNote">Reflète tes modifications actuelles (même non téléchargées). Les images ne s'affichent que si les fichiers existent réellement à cet endroit.</span>
    </div>
    <div class="preview-topbar-actions">
      <button class="btn btn-sm btn-outline" id="previewDesktopBtn" type="button">🖥 Ordinateur</button>
      <button class="btn btn-sm btn-outline" id="previewMobileBtn" type="button">📱 Mobile</button>
      <button class="btn btn-sm btn-outline" id="previewRefreshBtn" type="button">↻ Actualiser</button>
      <button class="btn btn-sm btn-primary" id="previewCloseBtn" type="button">✕ Fermer</button>
    </div>
  </div>
  <div class="preview-frame-wrap" id="previewFrameWrap">
    <iframe id="previewIframe" title="Aperçu du site Momox Dogs"></iframe>
  </div>
</div>

<!-- ============================= GUIDE COMPLET ============================= -->
<div id="guideOverlay" class="guide-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="guideTitle">
  <div class="guide-topbar">
    <div class="guide-topbar-left">
      <strong id="guideTitle">📘 Comment mettre à jour le site</strong>
      <span class="desc">Le guide complet : comprendre l'admin et ses panneaux, modifier un texte, changer une photo, publier en un clic, gérer les versions précédentes, personnaliser les couleurs, et corriger les problèmes courants.</span>
    </div>
    <div class="guide-topbar-actions">
      <button class="btn btn-sm btn-outline" id="guideCollapseBtn" type="button" title="Replier / déplier l'en-tête" aria-expanded="true">
        <svg class="guide-collapse-chevron" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M6 9l6 6 6-6"/></svg>
        <span class="guide-collapse-label">Réduire</span>
      </button>
      <button class="btn btn-sm btn-primary" id="guideCloseBtn" type="button">✕ Fermer</button>
    </div>
  </div>
  <div class="guide-progress"><div class="guide-progress-fill" id="guideProgressFill"></div></div>
  <div class="guide-body">
    <div class="guide-inner">

      <div class="guide-toc">
        <span class="guide-toc-label">Aller directement à…</span>
        <a href="#guide-vue">1. Comment ça marche</a>
        <a href="#guide-coupoeil">2. L'admin en un coup d'œil</a>
        <a href="#guide-panneaux">3. Les 16 panneaux, un par un</a>
        <a href="#guide-site">4. La structure du site</a>
        <a href="#guide-textes">5. Modifier un texte / un prix</a>
        <a href="#guide-photos">6. Photos : 3 façons de faire</a>
        <a href="#guide-publier">7. Publier en un clic</a>
        <a href="#guide-versions">8. Versions précédentes &amp; nettoyage des photos</a>
        <a href="#guide-verifier">9. Vérifier que c'est en ligne</a>
        <a href="#guide-conseils">10. Bonnes pratiques &amp; conseils de pro</a>
        <a href="#guide-astuces">11. Problèmes potentiels &amp; comment les corriger</a>
        <a href="#guide-checklist">✅ Aide-mémoire</a>
      </div>

      <!-- 1. VUE D'ENSEMBLE -->
      <div class="guide-step" id="guide-vue">
        <div class="guide-step-num">1</div>
        <div class="guide-step-body">
          <h3>🧩 Comment le site est construit</h3>
          <p>Trois éléments seulement, tous placés dans le même dossier sur l'hébergement :</p>
          <div class="guide-tree">
<span class="gt-folder">www/</span>  <span class="gt-dim">← dossier racine du site</span>
├── <span class="gt-file">index.html</span>       <span class="gt-dim">le site public — on n'y touche jamais directement</span>
├── <span class="gt-file">content.json</span>     <span class="gt-dim">tous les textes, prix, cours, réglages — modifié via cette page</span>
├── <span class="gt-file">admin.php</span>        <span class="gt-dim">cette page d'édition, protégée par mot de passe</span>
└── <span class="gt-folder">images/</span>
    ├── portrait-morgane.jpg
    ├── hero-chien.jpg
    ├── <span class="gt-folder">galerie/</span>   <span class="gt-dim">les photos de chiens accompagnés</span>
    │   └── chien1.jpg
    └── <span class="gt-folder">icons/</span>     <span class="gt-dim">logos techniques du site — jamais à modifier</span>
          </div>
          <p>Le principe est donc toujours le même, en deux temps :</p>
          <div class="guide-flow">
            <div class="guide-flow-step">
              <span class="guide-flow-step-icon">✏️</span>
              <span class="guide-flow-step-title">1. Je modifie</span>
              <span class="guide-flow-step-text">Textes, prix, photos, couleurs… dans cette page admin. Chaque champ est enregistré tout seul, en brouillon, au fur et à mesure.</span>
            </div>
            <div class="guide-flow-arrow">→</div>
            <div class="guide-flow-step">
              <span class="guide-flow-step-icon">🚀</span>
              <span class="guide-flow-step-title">2. Je publie</span>
              <span class="guide-flow-step-text">Un clic sur « Publier en ligne », relecture des changements, mot de passe — et le site est à jour pour tout le monde en quelques secondes.</span>
            </div>
          </div>
          <div class="guide-callout guide-callout-tip">
            <span class="guide-callout-icon">💡</span>
            <div><span class="guide-callout-title">Rien n'est jamais perdu — quatre filets de sécurité</span>
            <strong>1)</strong> un brouillon de tes modifications reste dans le navigateur même si tu fermes l'onglet par accident · <strong>2)</strong> ↶ « Annuler » / ↷ « Rétablir » en bas de page reviennent en arrière champ par champ · <strong>3)</strong> le bouton ↺ à côté d'un panneau (visible dès qu'il a des changements non publiés) remet <em>ce seul panneau</em> aux valeurs actuellement en ligne · <strong>4)</strong> chaque publication garde automatiquement une copie de la version précédente, consultable dans « 🗂 Anciennes versions ». Rien de ce que tu fais ici n'est définitif tant que tu n'as pas cliqué sur « 🚀 Publier en ligne ».</div>
          </div>
        </div>
      </div>

      <!-- 2. L'ADMIN EN UN COUP D'ŒIL -->
      <div class="guide-step" id="guide-coupoeil">
        <div class="guide-step-num">2</div>
        <div class="guide-step-body">
          <h3>🧭 L'admin en un coup d'œil</h3>
          <p>Avant le détail des 16 panneaux, voici tous les boutons et repères visuels que tu croiseras partout dans la page — une fois qu'ils sont familiers, le reste coule de source.</p>

          <div class="guide-panel-grid">
            <div class="guide-panel-card">
              <h4>🔝 En-tête, tout en haut</h4>
              <p><strong>👁 Aperçu du site</strong> montre le rendu réel avec tes modifications actuelles, sans rien publier · <strong>📘 Guide complet</strong> ouvre cette aide · <strong>🔒 Déconnexion</strong> ferme ta session admin · la <strong>pastille de statut</strong> à droite affiche <code>À jour</code> quand tout est publié, ou <code>« 3 modifications non téléchargées »</code> (le compte exact) dès que quelque chose est en attente.</p>
            </div>
            <div class="guide-panel-card">
              <h4>📋 Barre juste sous le titre</h4>
              <p><strong>▾ Tout déplier</strong> / <strong>▴ Tout replier</strong> ouvrent ou referment les 16 panneaux d'un coup — pratique pour tout relire avant de publier. La <strong>jauge « ✎ »</strong> n'apparaît que s'il y a des changements en attente : cliquer dessus ouvre la liste complète de tout ce qui a changé, sans rien publier — exactement la même fenêtre que celle affichée juste avant une publication.</p>
            </div>
            <div class="guide-panel-card">
              <h4>↺ Sur chaque panneau</h4>
              <p>Une petite pastille chiffrée apparaît à côté du titre d'un panneau dès qu'il contient des changements non publiés. Le bouton <strong>↺</strong> juste à côté (invisible sinon) remet <em>uniquement ce panneau</em> aux valeurs actuellement en ligne — utile pour annuler proprement une modification ratée sans toucher au reste.</p>
            </div>
            <div class="guide-panel-card">
              <h4>⬇️ Barre d'action, tout en bas</h4>
              <p><strong>↶ / ↷</strong> annulent ou rétablissent la dernière action, champ par champ · <strong>📤 Charger un fichier</strong> est une fonction technique de secours (recharger un <code>content.json</code> depuis l'ordinateur) · <strong>↺ Tout restaurer</strong> remet <em>toute</em> l'édition en cours aux valeurs actuellement en ligne (demande confirmation, à utiliser en dernier recours) · puis les deux boutons principaux : <strong>🚀 Publier en ligne</strong> et <strong>⬇ Télécharger content.json (secours)</strong>.</p>
            </div>
          </div>

          <div class="guide-callout guide-callout-info">
            <span class="guide-callout-icon">🔤</span>
            <div><span class="guide-callout-title">Pour lire les extraits ci-dessous</span>Certains encadrés grisés montrent à quoi ressemble le fichier <code>content.json</code> « vu de l'intérieur » — c'est juste à titre d'exemple, il n'y a jamais besoin d'y toucher soi-même. Deux mots reviennent souvent : <code>true</code> veut dire <strong>« activé / affiché sur le site »</strong>, et <code>false</code> veut dire <strong>« désactivé / caché »</strong>. Un clic sur un interrupteur dans l'admin bascule automatiquement entre les deux — pas besoin de taper ces mots. Les mentions <span class="guide-badge-new">Nouveau</span> repèrent les fonctions ajoutées depuis la dernière version de ce guide.</div>
          </div>
        </div>
      </div>

      <!-- 3. LES PANNEAUX DE L'ADMIN -->
      <div class="guide-step" id="guide-panneaux">
        <div class="guide-step-num">3</div>
        <div class="guide-step-body">
          <h3>🗂️ Les 16 panneaux, un par un</h3>
          <p>La page <code>admin.php</code> est composée de <strong>16 panneaux</strong> (les blocs qu'on ouvre/ferme en cliquant sur leur titre). La plupart modifient une partie précise du contenu du site — jamais <code>index.html</code> directement ; trois d'entre eux (envoi de photos, versions précédentes, nettoyage) agissent eux-mêmes directement sur le serveur, indépendamment du bouton « Publier ».</p>

          <div class="guide-panel-grid">

            <div class="guide-panel-card">
              <h4>👁 Visibilité du site</h4>
              <span class="guide-path">visibility.*</span>
              <p>7 interrupteurs qui cachent ou montrent des blocs entiers du site (Présentation, Pourquoi, Étiquettes, Galerie, Carte, Facebook, Instagram) sans jamais supprimer leur contenu.</p>
              <div class="guide-json">"visibility": {
  "pourquoi": true,   ← affichée sur le site
  "galerie": false    ← cachée du site
}</div>
              <p>Exemple : <strong>éteindre</strong> l'interrupteur « Pourquoi » fait passer sa valeur à <code>false</code> et la section disparaît du site. La <strong>rallumer</strong> repasse la valeur à <code>true</code> — son texte n'a jamais bougé du panneau « Pourquoi » entre-temps.</p>
            </div>

            <div class="guide-panel-card">
              <h4>Coordonnées de contact</h4>
              <span class="guide-path">contact.*</span>
              <p>Email, téléphone, ville, zone d'intervention (le texte affiché), liens Facebook et Instagram.</p>
              <div class="guide-json">"contact": {
  "email": "Morgane@momoxdogs.fr",
  "telephone": "07 82 25 26 88",
  "ville": "Taverny, France",
  "zone": "Val d'Oise et alentours"
}</div>
              <p>Ces champs alimentent aussi les boutons « Appeler » / « Écrire » et la carte de localisation de la section Contact.</p>
            </div>

            <div class="guide-panel-card">
              <h4>Page d'accueil</h4>
              <span class="guide-path">hero.*</span>
              <p>Le badge (optionnel), le grand titre et le texte d'introduction tout en haut du site — la toute première chose vue par un visiteur.</p>
              <div class="guide-json">"hero": {
  "badge": "Éducatrice canine certifiée",
  "titre": "Une éducation &lt;em&gt;sur-mesure&lt;/em&gt;, pas à pas.",
  "texte": "…"
}</div>
              <p>La balise <code>&lt;em&gt;...&lt;/em&gt;</code> autour d'un mot l'affiche en italique doré sur le site — c'est la seule mise en forme HTML autorisée dans ce champ. Laisser le badge vide le fait simplement disparaître.</p>
            </div>

            <div class="guide-panel-card">
              <h4>Présentation</h4>
              <span class="guide-path">presentation.*</span>
              <p>Le titre et les deux paragraphes de la section « Bonjour, je suis Morgane ». Le second paragraphe est optionnel : vide, il ne s'affiche simplement pas.</p>
              <div class="guide-json">"presentation": {
  "titre": "Bonjour, je suis Morgane."
}</div>
            </div>

            <div class="guide-panel-card">
              <h4>Section « Pourquoi »</h4>
              <span class="guide-path">pourquoi.*</span>
              <p>Eyebrow (le petit mot au-dessus du titre), titre, deux paragraphes et la citation mise en avant — eyebrow, second paragraphe et citation sont tous optionnels.</p>
              <div class="guide-json">"pourquoi": {
  "citation": "Il n'existe pas une méthode unique…"
}</div>
              <p>Ce panneau reste rempli même si la section est masquée depuis « 👁 Visibilité du site » — pratique pour préparer un texte avant de le publier.</p>
            </div>

            <div class="guide-panel-card">
              <h4>Étiquettes</h4>
              <span class="guide-path">tags (liste)</span>
              <p>Les petits mots-clés affichés sous la présentation (ex. « Éducation canine »).</p>
              <div class="guide-json">"tags": [
  "Éducation canine",
  "Balades canines"
]</div>
              <p>« Ajouter » insère une entrée en fin de liste ; la croix sur une étiquette la retire. <span class="guide-badge-new">Nouveau</span> Glisse une étiquette (↕) pour la déplacer directement à la souris ou au doigt, en plus des flèches ↑ / ↓.</p>
            </div>

            <div class="guide-panel-card">
              <h4>Cours &amp; tarifs</h4>
              <span class="guide-path">services (liste d'objets)</span>
              <p>Le panneau le plus riche : chaque carte est une prestation, repliée par défaut (titre, catégorie et prix restent visibles) — clique sur son titre pour la déplier.</p>
              <div class="guide-json">{
  "categorie": "Éducation",
  "titre": "Cours individuel",
  "image": "images/service-individuel.jpg",
  "description": "…",
  "prix": "50 €",
  "unite": "/ heure",
  "cadrage": "50% 30%",
  "cadrageDesktop": "70% 50%",
  "visible": true
}</div>
              <p class="desc"><code>cadrage</code> et <code>cadrageDesktop</code> sont facultatifs (réglables via les deux aperçus ci-dessous, jamais à la main) : absents ou vides, la photo reste centrée, exactement comme avant l'existence de ce champ.</p>
              <p>Actions disponibles sur chaque carte : l'interrupteur masque/affiche la prestation sur le site sans la supprimer · ↑ / ↓ changent l'ordre d'affichage · ⧉ « Dupliquer » crée une copie complète juste en dessous (pratique pour un cours qui ne diffère que par la durée ou le prix) · 🗑 « Supprimer » demande confirmation avant d'effacer la prestation. Catégorie, titre et description sont obligatoires ; prix et unité peuvent rester vides (aucun bloc prix ne s'affiche alors sur la carte du site).</p>
              <p><span class="guide-badge-new">Nouveau</span> <strong>Cadrage de la photo</strong> — sous le nom du fichier image, deux aperçus cliquables montrent la photo recadrée exactement comme sur le site : « 📱 Mobile » (vignette + fiche empilée) et « 🖥️ Grand écran » (fiche à deux colonnes, ≥ 900px, où la photo occupe toute la colonne de gauche en hauteur). Cliquer ou glisser dans un aperçu déplace le point de cadrage ; les flèches du clavier ajustent finement une fois l'aperçu sélectionné. « ↺ Centrer (défaut) » efface le réglage — sans réglage, la photo reste centrée comme avant l'ajout de cette fonction, donc un <code>content.json</code> plus ancien s'affiche normalement.</p>
            </div>

            <div class="guide-panel-card">
              <h4>Images principales</h4>
              <span class="guide-path">images.hero / images.contact / images.portrait</span>
              <p>Les trois grandes photos du site : image d'accueil, fond de la section contact, portrait de Morgane.</p>
              <div class="guide-json">"images": {
  "hero": "images/hero-chien.jpg",
  "portrait": "images/portrait-morgane.jpg"
}</div>
              <p>Seul le nom du fichier est demandé (ex. <code>hero-chien.jpg</code>) — le dossier <code>images/</code> est ajouté automatiquement devant. Chaque champ image dispose de deux boutons pour fournir la photo sans jamais taper le nom au clavier — voir l'étape 6 « Photos ».</p>
            </div>

            <div class="guide-panel-card">
              <h4>Galerie photo</h4>
              <span class="guide-path">galerie (liste d'objets) &amp; galerieTexte.titre</span>
              <p>Les photos de chiens accompagnés, chacune avec son nom de fichier et son texte alternatif (accessibilité + référencement). <span class="guide-badge-new">Nouveau</span> Un champ tout en haut permet aussi de personnaliser le titre affiché au-dessus de la galerie (laisser vide garde le titre par défaut).</p>
              <div class="guide-json">"galerie": [
  { "src": "images/galerie/chien1.jpg", "alt": "Chien en balade en forêt" }
]</div>
              <p><span class="guide-badge-new">Nouveau</span> Une zone « glisse-dépose » dédiée dans ce panneau envoie plusieurs photos <em>et</em> les ajoute à la liste en une seule fois — le vrai raccourci pour agrandir la galerie. La section Galerie n'apparaît sur le site que si <strong>deux conditions</strong> sont réunies : au moins <strong>une photo</strong> (avec son nom de fichier renseigné) ici, <em>et</em> l'interrupteur « galerie » réglé sur <code>true</code> dans « 👁 Visibilité du site ». Un compteur en haut du panneau indique en temps réel l'état de cette condition.</p>
            </div>

            <div class="guide-panel-card">
              <h4>📷 Envoyer des photos sur le serveur</h4>
              <span class="guide-path">action directe — pas de champ content</span>
              <p>Choisis d'abord le <strong>dossier de destination</strong> (🖼️ Images principales ou 📷 Galerie), dépose une ou plusieurs photos (glisser-déposer ou clic pour parcourir), renomme-les si besoin, puis « ⬆ Envoyer » : elles sont déposées <strong>immédiatement</strong> sur le serveur, sans attendre une publication.</p>
              <p>Le mot de passe admin est redemandé avant chaque envoi, même déjà connectée — même sécurité que pour une publication. Par sécurité aussi, l'envoi est <strong>refusé</strong> si un fichier du même nom existe déjà sur le serveur — renomme la nouvelle photo, ou supprime d'abord l'ancienne depuis « 🧹 Nettoyer les photos inutilisées ». C'est le panneau à utiliser pour envoyer des photos <em>à l'avance</em>, avant de savoir précisément où elles serviront.</p>
            </div>

            <div class="guide-panel-card">
              <h4>🗂 Anciennes versions</h4>
              <span class="guide-path">action directe — lit les sauvegardes du serveur</span>
              <p>Chaque publication garde automatiquement une copie de la version remplacée. <span class="guide-badge-new">Nouveau</span> Sous chaque carte, un résumé se charge tout seul (« 3 changements » ou « 🤷 Identique au contenu actuel de l'éditeur ») — pas besoin d'ouvrir quoi que ce soit pour se faire une idée. <span class="guide-badge-new">Nouveau</span> Le champ de recherche filtre les versions par date (« hier », « mars », « 12 »…) — pratique quand la liste s'allonge.</p>
              <p>« 👁 Comparer &amp; charger » affiche le détail complet puis recharge la version dans l'éditeur. <span class="guide-badge-new">Nouveau</span> Une case à cocher sur chaque carte permet d'en sélectionner plusieurs et de les <strong>supprimer définitivement</strong> du serveur avec « 🗑 Supprimer la sélection » — un simple ménage dans l'espace disque, sans rapport avec le site en ligne.</p>
              <div class="guide-callout guide-callout-warn" style="margin:10px 0 0;">
                <span class="guide-callout-icon">⚠️</span>
                <div>Recharger une sauvegarde ne publie rien toute seule : il faut ensuite cliquer sur « 🚀 Publier en ligne » pour l'appliquer réellement au site. À l'inverse, supprimer une ancienne version dans ce panneau est immédiat et irréversible — il n'y a pas de corbeille.</div>
              </div>
            </div>

            <div class="guide-panel-card">
              <h4>🧹 Nettoyer les photos inutilisées</h4>
              <span class="guide-path">action directe — compare le contenu en ligne au dossier images/</span>
              <p>Clique sur « 🔍 Rechercher les photos inutilisées » pour lancer l'analyse (elle ne se fait pas toute seule à l'ouverture du panneau). L'admin recherche alors les photos présentes sur le serveur (<code>images/</code> et <code>images/galerie/</code>) mais qui ne sont référencées nulle part dans le contenu <strong>actuellement en ligne</strong>. Les icônes du site (<code>images/icons/</code>) ne sont jamais proposées.</p>
              <p>Coche celles à supprimer et confirme — la suppression est immédiate et définitive sur le serveur. Si une ancienne sauvegarde référençait encore une photo supprimée ici, elle est effacée avec elle : impossible de recharger une sauvegarde vers une photo qui n'existe plus.</p>
            </div>

            <div class="guide-panel-card">
              <h4>Menu de navigation</h4>
              <span class="guide-path">nav (liste d'objets)</span>
              <p>Le texte des liens du menu, dans l'ordre où ils apparaissent en haut du site.</p>
              <div class="guide-json">"nav": [
  { "id": "presentation", "label": "Présentation" },
  { "id": "cours", "label": "Cours & tarifs" }
]</div>
              <p><code>id</code> doit correspondre exactement à l'identifiant d'une section du site (voir l'étape 4) — seul <code>label</code> (le texte visible) est à modifier ici. Le lien « Galerie » s'ajoute tout seul dès qu'une photo est ajoutée, pas besoin de le gérer ici.</p>
            </div>

            <div class="guide-panel-card">
              <h4>Couleurs du site</h4>
              <span class="guide-path">theme.*</span>
              <p>9 couleurs (fond, texte, accents dorés…) qui habillent l'ensemble du site.</p>
              <div class="guide-json">"theme": {
  "gold": "#ab8f66",
  "paper": "#fbf7ec"
}</div>
              <p><span class="guide-badge-new">Nouveau</span> Une galerie de <strong>préréglages complets</strong> (Chaleureux &amp; doré, Naturel &amp; végétal, Frais &amp; minéral, Élégant &amp; doux, Vif &amp; contrasté) permet de changer toute l'ambiance du site en un clic — chaque couleur reste ensuite modifiable individuellement si besoin. Le mini-aperçu du panneau s'illumine en survolant un champ, pour repérer où chaque couleur s'applique avant de valider. <span class="guide-badge-new">Nouveau</span> « Réinitialiser aux couleurs d'origine » ramène instantanément la palette dorée de départ.</p>
            </div>

            <div class="guide-panel-card">
              <h4>Pied de page</h4>
              <span class="guide-path">footer.*</span>
              <p>Le copyright (obligatoire) et la petite phrase tout en bas du site (optionnelle).</p>
              <div class="guide-json">"footer": {
  "copyright": "Momox Dogs — Morgane, éducatrice canine (BPE)"
}</div>
            </div>

            <div class="guide-panel-card">
              <h4>Référencement (SEO)</h4>
              <span class="guide-path">seo.*</span>
              <p>Le titre, la description et l'adresse du site utilisés par Google et par les réseaux sociaux quand le site est partagé.</p>
              <div class="guide-json">"seo": {
  "siteTitle": "Momox Dogs — Morgane, éducatrice canine (BPE)",
  "siteUrl": "https://momox-dogs.fr"
}</div>
              <p>Ces champs ne changent rien à l'apparence du site lui-même, seulement à ce qui s'affiche dans les résultats de recherche et les aperçus de partage (voir les conseils de l'étape 10 pour bien les remplir).</p>
            </div>

          </div>

          <p><strong>Rappel des outils globaux</strong> (détaillés à l'étape 2) — ils ne modifient rien tant qu'on ne clique pas dessus : <strong>👁 Aperçu du site</strong> et <strong>📘 Guide complet</strong> en haut · <strong>▾ Tout déplier / ▴ Tout replier</strong> et la <strong>jauge de changements en attente</strong> juste en dessous · la barre du bas (↶ annuler, ↷ rétablir, charger un fichier, ↺ tout restaurer) · puis les deux actions principales, <strong>🚀 Publier en ligne</strong> et <strong>⬇ Télécharger content.json (secours)</strong>.</p>
        </div>
      </div>

      <!-- 4. STRUCTURE DU SITE -->
      <div class="guide-step" id="guide-site">
        <div class="guide-step-num">4</div>
        <div class="guide-step-body">
          <h3>🏗️ La structure du site</h3>
          <p><code>index.html</code> affiche toujours les mêmes sections, dans le même ordre. Chacune est alimentée par une partie précise de <code>content.json</code> — c'est ce lien qui explique pourquoi modifier l'admin change le site sans jamais toucher à <code>index.html</code>.</p>
          <ul class="guide-site-list">
            <li><span class="guide-path">#accueil</span> En-tête + grand titre — alimenté par <code>hero.*</code></li>
            <li><span class="guide-path">#presentation</span> « Bonjour, je suis Morgane » — <code>presentation.*</code>, portrait par <code>images.portrait</code>, étiquettes par <code>tags</code></li>
            <li><span class="guide-path">#pourquoi</span> Pourquoi suivre des cours — <code>pourquoi.*</code> · s'affiche seulement si <code>visibility.pourquoi</code> vaut <code>true</code></li>
            <li><span class="guide-path">#cours</span> Grille des prestations — générée depuis <code>services</code> (une carte par prestation dont <code>visible</code> vaut <code>true</code>)</li>
            <li><span class="guide-path">#galerie</span> Galerie photo — générée depuis <code>galerie</code> · visible seulement si au moins 1 photo <em>et</em> <code>visibility.galerie</code> à <code>true</code></li>
            <li><span class="guide-path">#contact</span> Email, téléphone, zone, réseaux sociaux, carte — <code>contact.*</code> · réseaux et carte masquables un par un via <code>visibility.facebook</code>, <code>visibility.instagram</code>, <code>visibility.map</code></li>
            <li><span class="guide-path">pied de page</span> Copyright + phrase de fin — <code>footer.*</code></li>
          </ul>
          <p>Le <strong>menu de navigation</strong> (<code>nav</code>) pointe vers ces sections par leur identifiant (<code>presentation</code>, <code>pourquoi</code>, <code>cours</code>, <code>contact</code>…) : c'est pourquoi seul le texte affiché (<code>label</code>) doit être modifié dans l'admin, jamais l'<code>id</code>. Cliquer sur une carte de la section « Cours » ouvre une fiche détaillée avec la description complète — générée automatiquement, sans panneau dédié.</p>
          <div class="guide-callout guide-callout-info">
            <span class="guide-callout-icon">🗂️</span>
            <div><span class="guide-callout-title">Trois fichiers, trois rôles</span><code>index.html</code> = la structure et le design du site (fixe) · <code>content.json</code> = tous les textes, prix et réglages, mis à jour directement sur le serveur quand tu cliques sur « 🚀 Publier en ligne » · <code>admin.php</code> = cette page d'édition elle-même, protégée par mot de passe — elle n'a besoin d'aucune manipulation d'hébergement pour que tes changements soient publiés.</div>
          </div>
        </div>
      </div>

      <!-- 5. MODIFIER UN TEXTE -->
      <div class="guide-step" id="guide-textes">
        <div class="guide-step-num">5</div>
        <div class="guide-step-body">
          <h3>✏️ Modifier un texte, un prix ou un cours</h3>
          <ol>
            <li>Ouvre <code>admin.php</code> (garde-la en favori dans ton navigateur, tu y reviendras régulièrement).</li>
            <li>Clique sur le panneau qui t'intéresse pour le déplier (ex. « Cours &amp; tarifs », « Coordonnées de contact »…) et modifie directement les champs. Chaque changement est enregistré tout seul au fur et à mesure — la pastille en haut de page passe de <code>À jour</code> à <code>« N modifications non téléchargées »</code>.</li>
            <li>Utilise « 👁 Visibilité du site » ou l'interrupteur d'un cours si tu veux cacher temporairement un élément sans le supprimer.</li>
            <li>Avant de publier, un coup d'œil rapide : clique sur la <strong>jauge « ✎ »</strong> en haut de page pour relire d'un coup tout ce qui a changé, ou sur <strong>« 👁 Aperçu du site »</strong> pour voir le rendu réel.</li>
            <li>Une fois satisfaite, clique sur <strong>« 🚀 Publier en ligne »</strong> tout en bas de la page — voir l'étape 7 pour le détail de ce qui se passe ensuite.</li>
          </ol>
          <div class="guide-callout guide-callout-tip">
            <span class="guide-callout-icon">💡</span>
            <div><span class="guide-callout-title">Une erreur ? Pas de panique</span>Le bouton ↺ à côté du titre d'un panneau (visible dès qu'il a des changements) le remet uniquement lui aux valeurs actuellement en ligne. Pour tout annuler d'un coup, « ↺ Tout restaurer » dans la barre du bas fait la même chose sur l'ensemble de l'édition en cours — les deux redemandent confirmation avant d'agir.</div>
          </div>
        </div>
      </div>

      <!-- 6. PHOTOS -->
      <div class="guide-step" id="guide-photos">
        <div class="guide-step-num">6</div>
        <div class="guide-step-body">
          <h3>📷 Photos : 3 façons de faire (et laquelle choisir)</h3>
          <p>Tout se fait désormais <strong>depuis l'admin</strong>, sans FTP ni gestionnaire d'hébergement. Repère les deux petits boutons à droite de <em>chaque</em> champ « nom de fichier image » de la page :</p>
          <div class="guide-mock-field">
            <span class="guide-mock-field-path">images/</span>
            <span class="guide-mock-field-name">hero-chien.jpg</span>
            <span class="guide-mock-btn" title="Choisir une image déjà sur le serveur">🖼️</span>
            <span class="guide-mock-btn" title="Envoyer une nouvelle photo depuis l'appareil">📷</span>
          </div>
          <p>Trois méthodes, du plus rapide au plus complet :</p>
          <div class="guide-flow">
            <div class="guide-flow-step">
              <span class="guide-flow-step-icon">🖼️</span>
              <span class="guide-flow-step-title">A. Parcourir <span class="guide-badge-new">Nouveau</span></span>
              <span class="guide-flow-step-text">La photo est déjà sur le serveur (dans « Images principales » ou la Galerie) ? Clique sur 🖼️, choisis-la dans la fenêtre qui s'ouvre (avec recherche par nom). Si elle vient de l'autre dossier, elle y est automatiquement recopiée.</span>
            </div>
            <div class="guide-flow-or">ou</div>
            <div class="guide-flow-step">
              <span class="guide-flow-step-icon">📷</span>
              <span class="guide-flow-step-title">B. Envoyer depuis le champ <span class="guide-badge-new">Nouveau</span></span>
              <span class="guide-flow-step-text">C'est une photo toute neuve : clique sur 📷, choisis le fichier sur ton ordinateur ou ton téléphone. Un nom de fichier valide est généré automatiquement (modifiable), la photo est envoyée et associée au champ en une seule étape.</span>
            </div>
            <div class="guide-flow-or">ou</div>
            <div class="guide-flow-step">
              <span class="guide-flow-step-icon">📥</span>
              <span class="guide-flow-step-title">C. Le panneau dédié</span>
              <span class="guide-flow-step-text">Tu as plusieurs photos à envoyer d'avance, avant de savoir où elles serviront ? Utilise « 📷 Envoyer des photos sur le serveur » (glisser-déposer, plusieurs à la fois), puis choisis-les ensuite via 🖼️ dans le champ voulu.</span>
            </div>
          </div>
          <p>Pour la <strong>Galerie</strong> spécifiquement, la méthode la plus rapide reste sa propre zone de glisser-déposer (voir l'étape 3) : elle envoie et ajoute la photo à la liste en une seule action.</p>
          <p><strong>Avant d'envoyer une photo</strong>, quelques bonnes habitudes :</p>
          <ol>
            <li>Si tu envoies via le panneau dédié (méthode C), renomme le fichier sans espaces ni accents ni majuscules — utilise des tirets. Ex. <code>photo-chien-1.jpg</code>, pas <code>Photo Chien (1).JPG</code>. Avec les boutons 🖼️/📷 (méthodes A et B), ce n'est plus nécessaire : le nom est généré ou déjà propre.</li>
            <li>Format <code>.jpg</code>, <code>.png</code>, <code>.webp</code> ou <code>.gif</code>, et une taille raisonnable — voir les conseils techniques détaillés à l'étape 10 pour la résolution et le poids idéals.</li>
          </ol>
          <div class="guide-callout guide-callout-tip">
            <span class="guide-callout-icon">💡</span>
            <div><span class="guide-callout-title">Un filet de sécurité intégré</span>Si tu publies alors qu'un champ pointe vers une photo qui n'existe pas encore sur le serveur, l'admin le détecte tout seul et ouvre une fenêtre dédiée, avec une barre de progression photo par photo : dépose la photo manquante directement dedans (ou choisis un fichier déjà présent qui lui ressemble parmi les suggestions proposées) — la publication reprend ensuite automatiquement. Si l'envoi d'une seule photo échoue, seule celle-là peut être réessayée, sans perdre les autres déjà envoyées.</div>
          </div>
          <div class="guide-callout guide-callout-warn">
            <span class="guide-callout-icon">⚠️</span>
            <div><span class="guide-callout-title">Le nom doit correspondre exactement</span>Majuscules et minuscules comptent : si l'admin indique <code>chien.jpg</code> mais que le fichier envoyé s'appelle <code>Chien.JPG</code>, la photo n'apparaîtra pas sur le site. C'est pour ça que les boutons 🖼️/📷 sont recommandés : ils éliminent ce risque en gérant le nom pour toi. Et si une photo du même nom existe déjà sur le serveur, l'envoi est toujours refusé pour éviter d'écraser une image par erreur.</div>
          </div>
        </div>
      </div>

      <!-- 7. PUBLIER EN UN CLIC -->
      <div class="guide-step" id="guide-publier">
        <div class="guide-step-num">7</div>
        <div class="guide-step-body">
          <h3>🚀 Publier en un clic</h3>
          <p>Un seul bouton, tout en bas de la page : <strong>« 🚀 Publier en ligne »</strong>. En coulisse, l'admin déroule automatiquement quatre vérifications avant d'écrire quoi que ce soit sur le serveur :</p>
          <ol>
            <li><strong>Comparaison</strong> — l'admin compare ce que tu as modifié à ce qui est actuellement en ligne, et t'affiche la liste précise des changements (« Publier ces modifications ? »). Si rien n'a réellement changé, la publication s'arrête d'elle-même avec un message plutôt que de republier inutilement.</li>
            <li><strong>Vérification des photos</strong> — chaque photo référencée (accueil, portrait, cours, galerie…) est vérifiée sur le serveur. Si l'une d'elles manque, la fenêtre dédiée décrite à l'étape 6 s'ouvre pour la déposer ou corriger le champ — la publication reprend ensuite toute seule.</li>
            <li><strong>Mot de passe de confirmation</strong> — l'admin redemande le mot de passe admin, même déjà connectée. C'est une sécurité supplémentaire pour éviter qu'une publication parte par erreur (ex. téléphone laissé ouvert). Après plusieurs essais rapprochés, un compte à rebours apparaît (« 🔒 Trop de tentatives — réessaie dans Xs ») avant de pouvoir retenter.</li>
            <li><strong>Envoi</strong> — une fois tout confirmé, le nouveau contenu est écrit directement sur le serveur. L'ancienne version est automatiquement sauvegardée avant d'être remplacée (voir l'étape 8), et un message confirme la réussite.</li>
          </ol>
          <div class="guide-callout guide-callout-tip">
            <span class="guide-callout-icon">✨</span>
            <div><span class="guide-callout-title">Aucune étape ne peut être oubliée</span>À tout moment de ce parcours, cliquer sur « Annuler » interrompt la publication sans rien modifier sur le site — tes modifications restent intactes dans l'éditeur, prêtes à être publiées plus tard.</div>
          </div>

          <p><strong>Solution de secours — dépôt manuel sur l'hébergement</strong></p>
          <p>Si la publication directe est un jour indisponible (hébergement en maintenance, etc.), il reste possible de publier « à l'ancienne » :</p>
          <ol>
            <li>Clique sur <strong>« ⬇ Télécharger content.json (secours) »</strong> tout en bas de la page. Le fichier part dans ton dossier <em>Téléchargements</em>.</li>
            <li>Connecte-toi à l'espace de gestion de l'hébergement du site (OVH Manager, ou l'équivalent chez l'hébergeur utilisé), puis ouvre le gestionnaire de fichiers FTP de l'hébergement concerné (ou un logiciel FTP comme FileZilla avec les identifiants fournis par l'hébergeur).</li>
            <li>Dans le dossier où se trouve <code>index.html</code>, remplace <code>content.json</code> par le fichier téléchargé — vérifie qu'il s'appelle bien exactement <code>content.json</code>, sans <code>(1)</code> ajouté par le navigateur.</li>
          </ol>
        </div>
      </div>

      <!-- 8. VERSIONS PRÉCÉDENTES & NETTOYAGE -->
      <div class="guide-step" id="guide-versions">
        <div class="guide-step-num">8</div>
        <div class="guide-step-body">
          <h3>🗂️ Versions précédentes, comparaison &amp; nettoyage des photos</h3>
          <p><strong>Revenir en arrière après publication</strong> — le panneau <code>🗂 Anciennes versions</code> liste toutes les sauvegardes créées automatiquement à chaque publication, avec un résumé des changements qui se charge tout seul sous chaque carte. Utilise le champ de recherche pour filtrer par date si la liste s'allonge. Clique sur « 👁 Comparer &amp; charger » pour voir le détail complet, puis charge la version dans l'éditeur si c'est la bonne. Elle n'est <strong>pas encore appliquée au site</strong> à ce stade : il faut ensuite cliquer sur « 🚀 Publier en ligne » comme pour n'importe quelle autre modification.</p>
          <p><strong>Faire le ménage dans les anciennes versions</strong> — coche une ou plusieurs sauvegardes puis « 🗑 Supprimer la sélection » pour les effacer définitivement du serveur (utile si elles s'accumulent avec le temps) ; ce ménage n'a aucun effet sur le site actuellement en ligne.</p>
          <p><strong>Faire le ménage dans les photos</strong> — le panneau <code>🧹 Nettoyer les photos inutilisées</code> recherche, après avoir cliqué sur « 🔍 Rechercher les photos inutilisées », les fichiers de <code>images/</code> et <code>images/galerie/</code> qui ne sont plus utilisés par le contenu actuellement en ligne. Sélectionne ceux à retirer et confirme.</p>
          <div class="guide-callout guide-callout-warn">
            <span class="guide-callout-icon">⚠️</span>
            <div><span class="guide-callout-title">Suppression immédiate et définitive</span>Contrairement au reste de l'admin, supprimer une ancienne version ou une photo inutilisée n'attend pas le bouton « Publier » : ça a lieu tout de suite sur le serveur, sans corbeille. Vérifie toujours la sélection avant de confirmer.</div>
          </div>
        </div>
      </div>

      <!-- 9. VÉRIFIER -->
      <div class="guide-step" id="guide-verifier">
        <div class="guide-step-num">9</div>
        <div class="guide-step-body">
          <h3>🔍 Vérifier que la mise à jour est bien en ligne</h3>
          <ol>
            <li>Ouvre le site dans le navigateur (l'adresse habituelle) et vérifie que le changement apparaît.</li>
            <li>Si tu ne vois pas encore le changement, force le rafraîchissement de la page : <span class="guide-kbd">Ctrl</span> + <span class="guide-kbd">F5</span> sur ordinateur (ou <span class="guide-kbd">Cmd</span> + <span class="guide-kbd">Maj</span> + <span class="guide-kbd">R</span> sur Mac) — le navigateur garde parfois une ancienne version en mémoire.</li>
            <li>Jette aussi un œil sur téléphone, en navigation privée si besoin, pour être sûre que tout le monde voit la nouvelle version.</li>
          </ol>
        </div>
      </div>

      <!-- 10. BONNES PRATIQUES -->
      <div class="guide-step" id="guide-conseils">
        <div class="guide-step-num">10</div>
        <div class="guide-step-body">
          <h3>🌟 Bonnes pratiques &amp; conseils de pro</h3>
          <p>Ce que l'admin ne peut pas deviner à ta place — quelques réflexes qui font une vraie différence sur le rendu final et sur la visibilité du site.</p>
          <div class="guide-tip-grid">
            <div class="guide-tip-card">
              <h4>📐 Photos : la bonne taille</h4>
              <p>Vise environ <strong>1600 px de large</strong> et <strong>moins de 1-2 Mo</strong> par photo — largement suffisant à l'écran, et le site reste rapide à charger. Un outil gratuit comme squoosh.app (dans le navigateur, sans rien installer) permet de redimensionner et compresser une photo en quelques secondes avant de l'envoyer.</p>
            </div>
            <div class="guide-tip-card">
              <h4>🎨 Cohérence visuelle</h4>
              <p>Des photos avec une lumière et une ambiance proches (plutôt lumière naturelle, cadrages similaires) donnent un site plus professionnel qu'un mélange de styles très différents. Pour l'accueil et le portrait, privilégie un format plutôt horizontal ; pour la galerie, les deux formats fonctionnent bien.</p>
            </div>
            <div class="guide-tip-card">
              <h4>✍️ Décrire un cours</h4>
              <p>Une description efficace tient en 2-3 phrases : ce que le cours apporte concrètement au binôme maître-chien, plutôt qu'une liste de caractéristiques techniques. Termine par ce qui donne envie de prendre rendez-vous.</p>
            </div>
            <div class="guide-tip-card">
              <h4>💶 Prix clairs</h4>
              <p>Laisse le champ Prix <strong>vide</strong> plutôt que d'écrire « 0 € » si un tarif n'est pas encore fixé — aucun bloc prix ne s'affichera, au lieu d'un prix trompeur. Utilise le champ Unité (« / heure », « / séance », « / mois »…) pour éviter toute ambiguïté.</p>
            </div>
            <div class="guide-tip-card">
              <h4>🕒 Rythme de publication</h4>
              <p>Regroupe plusieurs petites modifications avant de publier plutôt qu'une publication par champ modifié — la jauge « ✎ » et « Tout déplier » sont faits pour ça. Relis toujours l'aperçu avant de valider, surtout après un gros changement de texte ou de couleurs.</p>
            </div>
            <div class="guide-tip-card">
              <h4>🔍 Bien remplir le SEO</h4>
              <p>Le titre du site (« Titre du site ») apparaît tel quel comme titre de l'onglet et du résultat Google — reste concis (60 caractères environ) et inclus le métier + la zone géographique. La description (150-160 caractères) doit donner envie de cliquer : c'est le texte affiché sous le lien dans les résultats de recherche.</p>
            </div>
            <div class="guide-tip-card">
              <h4>🔒 Le mot de passe admin</h4>
              <p>Ne le transmets jamais par SMS ou message non protégé si tu peux l'éviter, et évite de le laisser enregistré sur un appareil partagé. Après plusieurs mauvais essais, une pause de sécurité (compte à rebours) s'active automatiquement — c'est normal, pas un bug.</p>
            </div>
            <div class="guide-tip-card">
              <h4>💾 Une sauvegarde perso, en plus</h4>
              <p>En plus des sauvegardes automatiques du serveur, télécharge de temps en temps <code>content.json</code> via « ⬇ Télécharger content.json (secours) » et garde-le dans un dossier personnel — une sécurité supplémentaire qui ne coûte rien.</p>
            </div>
            <div class="guide-tip-card">
              <h4>📱 Toujours vérifier sur mobile</h4>
              <p>La majorité des visiteurs arrivent depuis leur téléphone. Utilise « 📱 Mobile » dans l'aperçu du site après une modification importante (nouvelle photo, nouveau texte long) pour t'assurer que tout reste lisible et bien cadré.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- 11. PROBLÈMES POTENTIELS -->
      <div class="guide-step" id="guide-astuces">
        <div class="guide-step-num">11</div>
        <div class="guide-step-body">
          <h3>🩹 Problèmes potentiels &amp; comment les corriger</h3>

          <p><strong>Affichage général du site</strong></p>
          <div class="guide-fix">
            <span class="guide-fix-title">Le site n'affiche plus rien / semble cassé après une mise à jour</span>
            <p><strong>Cause probable :</strong> rare avec la publication directe (elle valide le contenu avant d'écrire), mais peut arriver après un dépôt manuel d'un <code>content.json</code> incomplet ou modifié à la main.</p>
            <p><strong>Solution :</strong> ouvre « 🗂 Anciennes versions », charge la dernière sauvegarde qui fonctionnait via « 👁 Comparer &amp; charger », puis republie avec « 🚀 Publier en ligne » pour revenir à une version saine.</p>
          </div>
          <div class="guide-fix">
            <span class="guide-fix-title">Une section a disparu du site alors que je n'y ai pas touché</span>
            <p><strong>Cause probable :</strong> son interrupteur dans « 👁 Visibilité du site » a été désactivé par erreur, ou — pour la Galerie — le nombre de photos est repassé sous 5.</p>
            <p><strong>Solution :</strong> ouvre le panneau « Visibilité du site » et réactive l'interrupteur concerné ; pour la Galerie, vérifie aussi le compteur de photos en haut de son panneau.</p>
          </div>

          <p><strong>Images</strong></p>
          <div class="guide-fix">
            <span class="guide-fix-title">Une photo n'apparaît pas sur le site</span>
            <p><strong>Cause probable :</strong> le nom tapé dans l'admin ne correspond pas exactement (majuscules/minuscules comprises) au nom du fichier sur le serveur, ou le fichier n'est pas dans le bon dossier.</p>
            <p><strong>Solution :</strong> utilise plutôt le bouton 🖼️ du champ pour choisir la photo dans la liste — plus de risque de faute de frappe. Sinon, corrige le nom au caractère près et vérifie le dossier (<code>images/</code> ou <code>images/galerie/</code> pour la galerie).</p>
          </div>
          <div class="guide-fix">
            <span class="guide-fix-title">L'aperçu dans l'admin montre une image cassée</span>
            <p><strong>Cause probable :</strong> le fichier indiqué n'existe pas encore à cet emplacement — normal si la photo n'a pas encore été envoyée.</p>
            <p><strong>Solution :</strong> clique sur le bouton 📷 du champ pour l'envoyer directement depuis là, ou aucune action si l'envoi est prévu plus tard — l'image s'affichera dès que le fichier sera déposé au bon endroit.</p>
          </div>
          <div class="guide-fix">
            <span class="guide-fix-title">Le bouton 🖼️ ou 📷 à côté d'un champ ne réagit pas</span>
            <p><strong>Cause probable :</strong> la session admin a expiré en arrière-plan, ou la liste des photos du serveur n'a pas fini de se charger.</p>
            <p><strong>Solution :</strong> recharge la page <code>admin.php</code> et reconnecte-toi si besoin ; tes modifications non publiées restent dans le brouillon local du navigateur.</p>
          </div>

          <p><strong>Le formulaire d'admin lui-même</strong></p>
          <div class="guide-fix">
            <span class="guide-fix-title">content.json refuse de se charger dans l'admin</span>
            <p><strong>Cause probable :</strong> le fichier a été modifié à la main (hors admin) et contient une faute de syntaxe, ou ce n'est pas le bon fichier.</p>
            <p><strong>Solution :</strong> un message d'erreur explique le problème rencontré ; charge plutôt une sauvegarde précédente qui fonctionnait, via le bouton de chargement de fichier de la barre du bas.</p>
          </div>
          <div class="guide-fix">
            <span class="guide-fix-title">Un champ reste bordé de rouge</span>
            <p><strong>Cause probable :</strong> il est obligatoire et vide, ou mal formaté (ex. une adresse e-mail sans « @ », un lien sans <code>https://</code>, un nom de fichier image avec un espace).</p>
            <p><strong>Solution :</strong> corrige le champ signalé avant de publier — le message affiché sous le champ explique précisément ce qui est attendu.</p>
          </div>
          <div class="guide-fix">
            <span class="guide-fix-title">Mes modifications d'hier ont disparu</span>
            <p><strong>Cause probable :</strong> l'admin garde un brouillon automatique <em>dans le navigateur utilisé</em> — si un autre navigateur, un autre ordinateur, ou la navigation privée a été utilisé entre-temps, ce brouillon n'est pas partagé.</p>
            <p><strong>Solution :</strong> travaille toujours depuis le même navigateur pour un brouillon en cours, et publie (ou télécharge <code>content.json</code>) dès qu'une série de modifications est terminée — c'est le contenu publié qui devient la seule vraie source commune à tout le monde.</p>
          </div>
          <div class="guide-fix">
            <span class="guide-fix-title">Je ne retrouve plus le bouton « Copier le JSON »</span>
            <p><strong>Cause probable :</strong> il n'existe plus dans la version actuelle de l'admin — c'était une fonction technique rarement utile au quotidien.</p>
            <p><strong>Solution :</strong> pour une sauvegarde personnelle du contenu, utilise « ⬇ Télécharger content.json (secours) » ; pour annuler des changements, utilise ↺ (par panneau) ou « ↺ Tout restaurer » (barre du bas).</p>
          </div>

          <p><strong>Publication en ligne</strong></p>
          <div class="guide-fix">
            <span class="guide-fix-title">« Mot de passe incorrect » au moment de publier</span>
            <p><strong>Cause probable :</strong> le mot de passe ressaisi dans la fenêtre de confirmation ne correspond pas au mot de passe admin actuel.</p>
            <p><strong>Solution :</strong> ressaie en vérifiant majuscules et clavier (le bouton 👁 dans le champ permet d'afficher ce qui est tapé) ; rien n'a été modifié sur le site tant que ce message apparaît. Après plusieurs essais rapprochés, un compte à rebours « 🔒 Trop de tentatives » apparaît — attends qu'il se termine avant de réessayer.</p>
          </div>
          <div class="guide-fix">
            <span class="guide-fix-title">« Session expirée » pendant la publication</span>
            <p><strong>Cause probable :</strong> la session admin a expiré, par exemple après un long moment d'inactivité sur la page.</p>
            <p><strong>Solution :</strong> recharge la page <code>admin.php</code>, reconnecte-toi, puis republie — tes modifications non publiées restent dans le brouillon local du navigateur.</p>
          </div>
          <div class="guide-fix">
            <span class="guide-fix-title">Échec de la publication malgré un mot de passe correct</span>
            <p><strong>Cause probable :</strong> un problème de connexion ou un souci temporaire côté serveur.</p>
            <p><strong>Solution :</strong> le message précise qu'aucune modification ne devrait avoir été appliquée ; vérifie ta connexion et réessaie. En dernier recours, utilise « ⬇ Télécharger content.json (secours) » et dépose-le manuellement sur l'hébergement (voir l'étape 7).</p>
          </div>
          <div class="guide-fix">
            <span class="guide-fix-title">Un lien du menu de navigation ne mène nulle part</span>
            <p><strong>Cause probable :</strong> le champ <code>id</code> d'une entrée du menu a été modifié — il doit toujours correspondre à l'identifiant d'une section réelle du site (voir l'étape 4).</p>
            <p><strong>Solution :</strong> dans « Menu de navigation », ne modifie que le texte affiché ; si l'ordre ou le contenu semble incohérent, recharge une version précédente depuis « 🗂 Anciennes versions » puis republie.</p>
          </div>
          <div class="guide-fix">
            <span class="guide-fix-title">« Mot de passe incorrect » en envoyant une photo</span>
            <p><strong>Cause probable :</strong> même sécurité que pour la publication — le mot de passe est redemandé à chaque envoi, y compris depuis les boutons 🖼️/📷 ou le panneau « 📷 Envoyer des photos sur le serveur ».</p>
            <p><strong>Solution :</strong> ressaisis-le correctement ; rien n'est envoyé tant que ce message apparaît. Les photos déjà envoyées avant l'erreur (s'il y en avait plusieurs) restent, elles, bien sur le serveur.</p>
          </div>

          <p><strong>Versions &amp; nettoyage</strong></p>
          <div class="guide-fix">
            <span class="guide-fix-title">J'ai rechargé une ancienne version mais le site n'a pas changé</span>
            <p><strong>Cause probable :</strong> charger une sauvegarde depuis « 🗂 Anciennes versions » ne fait que la ramener dans l'éditeur, en attente.</p>
            <p><strong>Solution :</strong> clique ensuite sur « 🚀 Publier en ligne » pour l'appliquer réellement au site.</p>
          </div>
          <div class="guide-fix">
            <span class="guide-fix-title">J'ai supprimé une ancienne version par erreur</span>
            <p><strong>Cause probable :</strong> la suppression dans « 🗂 Anciennes versions » est immédiate et définitive, sans corbeille.</p>
            <p><strong>Solution :</strong> si le site en ligne actuel est correct, aucune conséquence — cette sauvegarde n'était qu'un point de retour possible parmi d'autres. Vérifie simplement qu'une version plus ancienne encore présente peut te servir de filet si besoin à l'avenir.</p>
          </div>
          <div class="guide-fix">
            <span class="guide-fix-title">J'ai supprimé une photo « inutilisée » qui servait en fait ailleurs</span>
            <p><strong>Cause probable :</strong> le nettoyage compare au contenu <em>actuellement en ligne</em> — une photo tout juste ajoutée dans l'éditeur mais pas encore publiée peut donc être proposée par erreur.</p>
            <p><strong>Solution :</strong> publie d'abord tes modifications en cours, puis seulement ensuite utilise « 🧹 Nettoyer les photos inutilisées » ; si la photo a déjà été supprimée par erreur, renvoie-la simplement via 📷 ou le panneau dédié.</p>
          </div>
          <div class="guide-fix">
            <span class="guide-fix-title">Une prestation dupliquée traîne dans la liste « Cours &amp; tarifs »</span>
            <p><strong>Cause probable :</strong> un clic sur ⧉ « Dupliquer » crée une copie complète juste après l'originale (avec « (copie) » ajouté au titre) — pratique, mais facile à créer par erreur.</p>
            <p><strong>Solution :</strong> déplie la carte en trop et clique sur 🗑 « Supprimer » (confirmation demandée), puis publie.</p>
          </div>

          <div class="guide-callout guide-callout-tip">
            <span class="guide-callout-icon">💡</span>
            <div><span class="guide-callout-title">Le réflexe qui évite la plupart des problèmes</span>Avant de publier : utilise « 👁 Aperçu du site » pour voir le rendu réel, relis la liste des changements affichée par la fenêtre de confirmation, et préfère les boutons 🖼️/📷 à la saisie manuelle pour les noms de fichiers image.</div>
          </div>
        </div>
      </div>

      <!-- CHECKLIST -->
      <div id="guide-checklist" class="guide-checklist" style="scroll-margin-top:18px;">
        <h3>✅ Aide-mémoire — les 4 étapes à chaque mise à jour<span class="guide-checklist-done" id="guideChecklistDone">🎉 Bravo, tu es prête !</span></h3>
        <label><input type="checkbox"><span>Je modifie mes textes / prix / photos dans cette page admin, panneau par panneau — pour les photos, je privilégie les boutons 🖼️ (choisir une photo déjà en ligne) et 📷 (envoyer une nouvelle photo) directement sur chaque champ.</span></label>
        <label><input type="checkbox"><span>Je relis la jauge « ✎ » de changements en attente et je vérifie avec « 👁 Aperçu du site » (ordinateur et mobile).</span></label>
        <label><input type="checkbox"><span>Je clique sur « 🚀 Publier en ligne », je relis les changements affichés, je résous les photos manquantes s'il y en a, puis je confirme avec mon mot de passe.</span></label>
        <label><input type="checkbox"><span>Je recharge le site (Ctrl+F5) pour vérifier que tout est bien en ligne.</span></label>
      </div>

    </div>
  </div>
</div>

<!-- Sélecteur de couleur précis (popover partagé, positionné en JS) -->
<div id="colorPickerPopover" class="color-picker-popover" role="dialog" aria-label="Choisir une couleur précisément">
  <div class="cp-sv" id="cpSV">
    <div class="cp-sv-white"></div>
    <div class="cp-sv-black"></div>
    <div class="cp-sv-thumb" id="cpSVThumb"></div>
  </div>
  <div class="cp-hue-row">
    <input type="range" id="cpHue" class="cp-hue-slider" min="0" max="360" value="0" step="1" aria-label="Teinte">
  </div>
  <div class="cp-hex-row">
    <span class="cp-hex-prefix">#</span>
    <input type="text" id="cpHexInput" class="cp-hex-input" maxlength="6" placeholder="ab8f66" aria-label="Code hexadécimal">
    <div class="cp-current-swatch" id="cpCurrentSwatch"></div>
  </div>
  <div>
    <div class="cp-quick-label">Couleurs rapides</div>
    <div class="cp-quick-grid" id="cpQuickGrid"></div>
  </div>
</div>

<div id="confirmOverlay" class="confirm-overlay" style="display:none;">
  <div class="confirm-modal" id="confirmModal" role="alertdialog" aria-modal="true" aria-labelledby="confirmTitle" aria-describedby="confirmMessage" tabindex="-1">
    <div class="confirm-icon" id="confirmIcon">❓</div>
    <h3 class="confirm-title" id="confirmTitle">Confirmer</h3>
    <p class="confirm-message" id="confirmMessage"></p>
    <div class="confirm-actions">
      <button type="button" class="btn btn-outline" id="confirmCancelBtn">Annuler</button>
      <button type="button" class="btn btn-primary" id="confirmOkBtn">Confirmer</button>
    </div>
  </div>
</div>

<div id="diffOverlay" class="diff-overlay" style="display:none;">
  <div class="diff-modal" id="diffModal" role="alertdialog" aria-modal="true" aria-labelledby="diffTitle">
    <div class="diff-head">
      <div class="diff-head-row">
        <div class="diff-head-icon" id="diffIcon">✨</div>
        <div class="diff-head-text">
          <h3 id="diffTitle">Modifications détectées</h3>
          <p id="diffSubtitle"></p>
        </div>
        <button type="button" class="diff-close-btn" id="diffCloseBtn" aria-label="Fermer">✕</button>
      </div>
      <div class="diff-stats" id="diffStats"></div>
      <p class="diff-sections-summary" id="diffSectionsSummary" style="display:none;"></p>
    </div>
    <div class="diff-body" id="diffBody"></div>
    <div class="diff-foot">
      <p class="diff-count" id="diffCount"></p>
      <div class="diff-actions">
        <button type="button" class="btn btn-outline" id="diffCancelBtn">Annuler</button>
        <button type="button" class="btn btn-primary" id="diffOkBtn">Valider</button>
      </div>
    </div>
  </div>
</div>

<div id="pwConfirmOverlay" class="confirm-overlay" style="display:none;">
  <div class="confirm-modal" id="pwConfirmModal" role="alertdialog" aria-modal="true" aria-labelledby="pwConfirmTitle" aria-describedby="pwConfirmMessage" tabindex="-1">
    <div class="confirm-icon pw-confirm-icon">🔒</div>
    <h3 class="confirm-title" id="pwConfirmTitle">Confirme ta publication</h3>
    <p class="confirm-message" id="pwConfirmMessage">Pour publier une mise à jour du site, ressaisis le mot de passe admin.</p>
    <form class="pw-confirm-form" id="pwConfirmForm">
      <label for="pwConfirmInput">Mot de passe</label>
      <div class="pw-confirm-input-wrap">
        <input type="password" id="pwConfirmInput" name="pwConfirmInput" autocomplete="current-password" required>
        <button type="button" class="pw-confirm-toggle" id="pwConfirmToggle" aria-label="Afficher le mot de passe">👁</button>
      </div>
      <div class="pw-confirm-error" id="pwConfirmError"></div>
      <p class="pw-confirm-hint">Demandé à chaque publication, même si tu es déjà connectée à l’admin.</p>
    </form>
    <div class="confirm-actions" style="margin-top:18px;">
      <button type="button" class="btn btn-outline" id="pwConfirmCancelBtn">Annuler</button>
      <button type="button" class="btn btn-primary" id="pwConfirmOkBtn">🚀 Publier</button>
    </div>
  </div>
</div>

<div id="photosOverlay" class="diff-overlay pm-overlay" style="display:none;">
  <div class="diff-modal pm-modal" id="photosModal" role="alertdialog" aria-modal="true" aria-labelledby="photosTitle">
    <div class="diff-head">
      <div class="diff-head-row">
        <div class="diff-head-icon" id="photosIcon">📷</div>
        <div class="diff-head-text">
          <h3 id="photosTitle">Photos manquantes</h3>
          <p id="photosSubtitle"></p>
        </div>
      </div>
    </div>
    <div class="diff-body pm-body">
      <div class="pm-dropzone" id="pmDropzone" tabindex="0" role="button">
        <div class="pm-dropzone-icon">⬆️</div>
        <div class="pm-dropzone-text"><strong>Dépose tes photos ici</strong> ou clique pour les choisir — on les associe automatiquement aux bons emplacements ci-dessous.</div>
        <input type="file" id="pmDropInput" accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif" multiple style="display:none;">
      </div>
      <div id="pmSlots"></div>
      <div id="pmUnmatchedWrap" style="display:none;">
        <p class="diff-section-title" style="margin-top:18px;"><span class="diff-section-icon">📎</span>Fichiers non associés</p>
        <div id="pmUnmatched"></div>
      </div>
    </div>
    <div class="diff-foot">
      <div class="pm-progress" id="pmProgress" style="display:none;">
        <div class="pm-progress-track"><div class="pm-progress-fill" id="pmProgressFill"></div></div>
        <div class="pm-progress-label" id="pmProgressLabel"></div>
      </div>
      <p class="diff-count" id="pmCount"></p>
      <div class="diff-actions">
        <button type="button" class="btn btn-outline" id="photosCancelBtn">Annuler la publication</button>
        <button type="button" class="btn btn-primary" id="photosOkBtn" disabled>Envoyer les photos et publier</button>
      </div>
    </div>
  </div>
</div>

<div id="imagePickerOverlay" class="diff-overlay" style="display:none;">
  <div class="diff-modal" id="imagePickerModal" role="alertdialog" aria-modal="true" aria-labelledby="imagePickerTitle">
    <div class="diff-head">
      <div class="diff-head-row">
        <div class="diff-head-icon">🖼️</div>
        <div class="diff-head-text">
          <h3 id="imagePickerTitle">Choisir une image déjà sur le serveur</h3>
          <p id="imagePickerSubtitle">Sélectionne une photo parmi celles déjà envoyées.</p>
        </div>
      </div>
    </div>
    <div class="diff-body">
      <div class="ip-tabs" id="ipTabs">
        <button type="button" class="ip-tab" data-ip-tab="all">Toutes</button>
        <button type="button" class="ip-tab" data-ip-tab="images">Images principales</button>
        <button type="button" class="ip-tab" data-ip-tab="galerie">Galerie</button>
      </div>
      <div class="ip-search"><input type="text" id="ipSearchInput" placeholder="Rechercher un nom de fichier…"></div>
      <div id="ipGridWrap"><div class="ip-loading" id="ipLoading">Chargement des photos du serveur…</div></div>
      <div class="ip-cross-note" id="ipCrossNote" style="display:none;"></div>
    </div>
    <div class="diff-foot">
      <div class="diff-actions">
        <button type="button" class="btn btn-outline" id="imagePickerCancelBtn">Annuler</button>
        <button type="button" class="btn btn-primary" id="imagePickerConfirmBtn" disabled>Utiliser cette photo</button>
      </div>
    </div>
  </div>
</div>

<div id="uploadNameOverlay" class="confirm-overlay" style="display:none;">
  <div class="confirm-modal" id="uploadNameModal" role="alertdialog" aria-modal="true" aria-labelledby="uploadNameTitle" aria-describedby="uploadNameOriginal" tabindex="-1">
    <img class="upload-name-photo" id="uploadNamePhoto" src="" alt="">
    <h3 class="confirm-title" id="uploadNameTitle">Nom du fichier sur le serveur</h3>
    <p class="upload-name-original" id="uploadNameOriginal">Photo choisie : <strong id="uploadNameOriginalName"></strong></p>
    <form class="upload-name-form" id="uploadNameForm">
      <label for="uploadNameInput">Nom sous lequel l’enregistrer</label>
      <div class="filepath-row">
        <span class="filepath-prefix" id="uploadNamePrefix"></span>
        <input type="text" id="uploadNameInput" name="uploadNameInput" autocomplete="off" required>
      </div>
      <p class="upload-name-hint">Généré automatiquement à partir du nom d’origine — modifiable, en gardant la même extension.</p>
    </form>
    <div class="confirm-actions" style="margin-top:18px;">
      <button type="button" class="btn btn-outline" id="uploadNameCancelBtn">Annuler</button>
      <button type="button" class="btn btn-primary" id="uploadNameOkBtn">✅ Utiliser ce nom</button>
    </div>
  </div>
</div>

<div id="errorBanner" class="error-banner" style="display:none;" role="alert">
  <span class="error-banner-icon">🛡️</span>
  <span class="error-banner-text" id="errorBannerText"></span>
  <button type="button" class="error-banner-close" id="errorBannerClose" aria-label="Fermer">✕</button>
</div>

<script>
/* =====================================================================
   ÉTAT
   ===================================================================== */
let CONTENT = null;
/* Dernière version connue de content.json réellement sur le serveur —
   sert uniquement à calculer les pastilles "X modifications" affichées
   sur chaque section repliée (indépendant de l'historique annuler/
   rétablir, qui lui peut repartir d'un brouillon repris). */
let PRISTINE_CONTENT = null;
let dirty = false;
const DRAFT_KEY = 'momoxdogs_admin_draft_v1';

const $ = (sel, root=document) => root.querySelector(sel);
const $$ = (sel, root=document) => Array.from(root.querySelectorAll(sel));

/* =====================================================================
   FETCH AVEC TIMEOUT — évite qu'une requête réseau reste bloquée
   indéfiniment (mauvaise connexion, serveur qui ne répond plus) sans
   jamais donner de retour à l'utilisatrice. Au-delà du délai, la
   requête est annulée et une erreur claire et explicite est levée
   plutôt que de laisser un bouton « en cours… » figé pour toujours.
   ===================================================================== */
async function fetchWithTimeout(url, options = {}, timeoutMs = 25000){
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);
  try {
    return await fetch(url, Object.assign({}, options, { signal: controller.signal }));
  } catch(e){
    if (e.name === 'AbortError'){
      throw new Error('Le serveur met trop de temps à répondre (connexion lente ou coupée). Vérifie ta connexion internet et réessaie.');
    }
    throw new Error('Impossible de contacter le serveur — vérifie ta connexion internet et réessaie.');
  } finally {
    clearTimeout(timer);
  }
}

/* Lit une réponse JSON de façon robuste : ne lève jamais d'exception
   si le corps n'est pas du JSON valide (page d'erreur HTML du serveur,
   coupure en plein milieu de la réponse, etc.) — renvoie null dans ce
   cas, à charge de l'appelant d'afficher un message générique. */
async function readJsonSafe(res){
  try { return await res.json(); } catch(e){ return null; }
}

/* =====================================================================
   CONFIRMATION PERSONNALISÉE — remplace window.confirm()
   Usage : const ok = await customConfirm({ title, message, okText,
           cancelText, danger, icon }); // ou juste une string comme message
   ===================================================================== */
function customConfirm(opts){
  if (typeof opts === 'string') opts = { message: opts };
  const {
    title = 'Confirmer l\u2019action',
    message = '',
    okText = 'Confirmer',
    cancelText = 'Annuler',
    danger = false,
    icon = null
  } = opts;

  const overlay  = $('#confirmOverlay');
  const modal    = $('#confirmModal');
  const iconEl   = $('#confirmIcon');
  const titleEl  = $('#confirmTitle');
  const msgEl    = $('#confirmMessage');
  const okBtn    = $('#confirmOkBtn');
  const cancelBtn= $('#confirmCancelBtn');

  titleEl.textContent = title;
  msgEl.textContent = message;
  okBtn.textContent = okText;
  cancelBtn.textContent = cancelText;
  iconEl.textContent = icon || (danger ? '\u{1F5D1}\uFE0F' : '\u2753');
  modal.classList.toggle('is-danger', !!danger);
  okBtn.className = 'btn ' + (danger ? 'btn-danger' : 'btn-primary');

  const previouslyFocused = document.activeElement;

  return new Promise((resolve) => {
    let settled = false;

    function close(result){
      if (settled) return;
      settled = true;
      overlay.classList.remove('show');
      document.removeEventListener('keydown', onKeydown, true);
      setTimeout(() => { overlay.style.display = 'none'; }, 260);
      if (previouslyFocused && typeof previouslyFocused.focus === 'function') previouslyFocused.focus();
      resolve(result);
    }

    function onKeydown(e){
      if (e.key === 'Escape'){ e.preventDefault(); close(false); }
      else if (e.key === 'Enter'){ e.preventDefault(); close(document.activeElement === okBtn); }
      else if (e.key === 'Tab'){
        e.preventDefault();
        const focusables = [cancelBtn, okBtn];
        const idx = focusables.indexOf(document.activeElement);
        const next = e.shiftKey ? (idx <= 0 ? focusables.length - 1 : idx - 1) : (idx === focusables.length - 1 ? 0 : idx + 1);
        focusables[next].focus();
      }
    }

    okBtn.onclick = () => close(true);
    cancelBtn.onclick = () => close(false);
    overlay.onclick = (e) => { if (e.target === overlay) close(false); };

    overlay.style.display = 'flex';
    document.addEventListener('keydown', onKeydown, true);
    requestAnimationFrame(() => {
      overlay.classList.add('show');
      cancelBtn.focus();
    });
  });
}

/* =====================================================================
   RECONFIRMATION DU MOT DE PASSE AVANT PUBLICATION
   Redemandée à chaque publication, juste avant l'envoi final (après
   acceptation de la comparaison des changements et résolution des
   photos manquantes), même si la session admin est déjà ouverte.
   Vérifiée une première fois ici via inc/verify-password.php pour un
   retour immédiat ; revérifiée ensuite côté serveur dans
   inc/save-content.php, qui reste le seul rempart réellement fiable.
   ===================================================================== */
async function verifyPublishPassword(password){
  try {
    const res = await fetchWithTimeout('inc/verify-password.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ password })
    }, 15000);
    const data = await readJsonSafe(res);
    if (res.status === 401){
      return { ok:false, locked:false, message: (data && data.message) || 'Session expirée — recharge la page admin.php et reconnecte-toi.' };
    }
    if (res.status === 423){
      return { ok:false, locked:true, retryAfter: (data && data.retry_after) || 30, message: (data && data.message) || 'Trop de tentatives.' };
    }
    if (!data){
      return { ok:false, locked:false, message: 'Réponse du serveur illisible pendant la vérification.' };
    }
    return { ok: !!data.ok, locked:false, message: data.message };
  } catch(e){
    return { ok:false, locked:false, message: e.message || 'Impossible de contacter le serveur.' };
  }
}

function askPublishPassword(opts){
  opts = opts || {};
  const overlay   = $('#pwConfirmOverlay');
  const modal     = $('#pwConfirmModal');
  const input     = $('#pwConfirmInput');
  const toggleBtn = $('#pwConfirmToggle');
  const errorEl   = $('#pwConfirmError');
  const okBtn     = $('#pwConfirmOkBtn');
  const cancelBtn = $('#pwConfirmCancelBtn');
  const titleEl   = $('#pwConfirmTitle');
  const messageEl = $('#pwConfirmMessage');
  const iconEl    = modal.querySelector('.pw-confirm-icon');

  // Textes par défaut = ceux de la publication ; un appelant (ex. l'envoi
  // de photos) peut les personnaliser sans dupliquer toute la logique.
  titleEl.textContent = opts.title || 'Confirme ta publication';
  messageEl.textContent = opts.message || 'Pour publier une mise à jour du site, ressaisis le mot de passe admin.';
  if (iconEl) iconEl.textContent = opts.icon || '🔒';
  const originalOkLabel = opts.okLabel || '🚀 Publier';
  okBtn.textContent = originalOkLabel;
  const previouslyFocused = document.activeElement;

  input.value = '';
  input.type = 'password';
  toggleBtn.textContent = '👁';
  input.classList.remove('is-error');
  errorEl.style.display = 'none';
  errorEl.textContent = '';
  okBtn.disabled = false;
  cancelBtn.disabled = false;
  input.disabled = false;
  okBtn.textContent = originalOkLabel;

  return new Promise((resolve) => {
    let settled = false;
    let lockTimer = null;

    function clearLockTimer(){ if (lockTimer){ clearInterval(lockTimer); lockTimer = null; } }

    function close(result){
      if (settled) return;
      settled = true;
      clearLockTimer();
      overlay.classList.remove('show');
      setTimeout(() => { overlay.style.display = 'none'; }, 260);
      document.removeEventListener('keydown', onKeydown, true);
      toggleBtn.removeEventListener('click', onToggle);
      okBtn.removeEventListener('click', onOk);
      cancelBtn.removeEventListener('click', onCancel);
      form.removeEventListener('submit', onSubmit);
      if (previouslyFocused && typeof previouslyFocused.focus === 'function') previouslyFocused.focus();
      resolve(result);
    }

    function showFieldError(msg){
      errorEl.textContent = msg;
      errorEl.style.display = 'block';
      input.classList.add('is-error');
      modal.classList.remove('is-shake');
      void modal.offsetWidth; // relance l'animation même si elle vient de jouer
      modal.classList.add('is-shake');
      input.value = '';
      input.focus();
    }

    function startLockCountdown(seconds){
      clearLockTimer();
      let remaining = Math.max(1, Math.round(seconds));
      okBtn.disabled = true;
      input.disabled = true;
      input.classList.add('is-error');
      const tick = () => {
        errorEl.textContent = '🔒 Trop de tentatives — réessaie dans ' + remaining + 's.';
        errorEl.style.display = 'block';
        if (remaining <= 0){
          clearLockTimer();
          okBtn.disabled = false;
          input.disabled = false;
          input.classList.remove('is-error');
          errorEl.style.display = 'none';
          input.focus();
          return;
        }
        remaining--;
      };
      tick();
      lockTimer = setInterval(tick, 1000);
    }

    async function onOk(){
      const pwd = input.value;
      if (!pwd){ showFieldError('Saisis le mot de passe avant de continuer.'); return; }
      okBtn.disabled = true;
      cancelBtn.disabled = true;
      input.disabled = true;
      okBtn.textContent = 'Vérification…';
      const result = await verifyPublishPassword(pwd);
      okBtn.textContent = originalOkLabel;
      cancelBtn.disabled = false;

      if (result.ok){
        close({ ok: true, password: pwd });
        return;
      }
      okBtn.disabled = false;
      input.disabled = false;
      if (result.locked){
        startLockCountdown(result.retryAfter || 30);
      } else {
        showFieldError(result.message || 'Mot de passe incorrect — réessaie.');
      }
    }
    function onCancel(){ close({ ok:false }); }
    function onSubmit(e){ e.preventDefault(); if (!okBtn.disabled) onOk(); }
    function onToggle(){
      input.type = input.type === 'password' ? 'text' : 'password';
      toggleBtn.textContent = input.type === 'password' ? '👁' : '🙈';
      input.focus();
    }
    function onKeydown(e){
      if (e.key === 'Escape'){ e.preventDefault(); onCancel(); }
    }

    const form = $('#pwConfirmForm');
    form.addEventListener('submit', onSubmit);
    okBtn.addEventListener('click', onOk);
    cancelBtn.addEventListener('click', onCancel);
    toggleBtn.addEventListener('click', onToggle);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) onCancel(); }, { once:false });
    document.addEventListener('keydown', onKeydown, true);

    overlay.style.display = 'flex';
    requestAnimationFrame(() => {
      overlay.classList.add('show');
      input.focus();
    });
  });
}

function hideLoadingOverlay(){
  const overlay = $('#loadingOverlay');
  if (!overlay || overlay.style.display === 'none') return;
  overlay.classList.add('is-hiding');
  setTimeout(() => { overlay.style.display = 'none'; }, 360);
}

function showToast(msg, isError=false){
  const t = $('#toast');
  t.textContent = msg;
  t.className = 'toast show' + (isError ? ' error' : '');
  clearTimeout(showToast._timer);
  // Durée adaptée à la longueur du message : un message court reste
  // 2.6s comme avant, un message détaillé (plusieurs lignes d'erreurs)
  // reste affiché plus longtemps pour laisser le temps de le lire.
  const duration = Math.min(9000, Math.max(2600, msg.length * 55));
  showToast._timer = setTimeout(()=> t.classList.remove('show'), duration);
}

/* =====================================================================
   FILET DE SÉCURITÉ GLOBAL
   Si une erreur JS imprévue survient malgré toutes les protections
   ci-dessous, on ne veut jamais laisser la page se figer sans
   explication. On affiche une bannière discrète mais claire, et on
   tente aussitôt une sauvegarde d'urgence de CONTENT dans le brouillon
   local, pour maximiser les chances de ne rien perdre.
   ===================================================================== */
function showErrorBanner(message){
  const banner = $('#errorBanner');
  $('#errorBannerText').textContent = message;
  banner.style.display = 'flex';
  requestAnimationFrame(() => banner.classList.add('show'));
}
function hideErrorBanner(){
  const banner = $('#errorBanner');
  banner.classList.remove('show');
  setTimeout(() => { banner.style.display = 'none'; }, 300);
}
$('#errorBannerClose').addEventListener('click', hideErrorBanner);

function emergencySave(){
  if (!CONTENT) return false;
  try { localStorage.setItem(DRAFT_KEY, JSON.stringify(CONTENT)); return true; }
  catch(e){ return false; }
}

let lastGlobalErrorAt = 0;
function handleUnexpectedError(err){
  const now = Date.now();
  if (now - lastGlobalErrorAt < 4000) return; // évite le spam si l'erreur se répète en boucle
  lastGlobalErrorAt = now;
  console.error('Erreur inattendue :', err);
  const saved = emergencySave();
  showErrorBanner(
    saved
      ? 'Une erreur inattendue est survenue. Tes modifications ont été sauvegardées localement par sécurité — recharge la page si l\u2019affichage semble bloqué.'
      : 'Une erreur inattendue est survenue et la sauvegarde locale a échoué. Télécharge ton fichier maintenant si possible, avant de recharger la page.'
  );
}
window.addEventListener('error', (e) => handleUnexpectedError(e.error || e.message));
window.addEventListener('unhandledrejection', (e) => handleUnexpectedError(e.reason));

/* =====================================================================
   VALIDATION DES FORMULAIRES
   Chaque champ « validable » est enregistré dans un « bucket » par
   section (contact/héros/services/galerie/...). validateAll() relance
   la vérification de tous les champs (utilisé avant le téléchargement
   ou la copie du JSON) et renvoie le premier champ en erreur pour
   pouvoir l'amener à l'écran.
   ===================================================================== */
const SECTION_VALIDATORS = {};
function setSectionValidators(section, arr){ SECTION_VALIDATORS[section] = arr; }

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
const URL_RE = /^https?:\/\/[^\s]+\.[^\s]+/i;
const IMAGE_EXT_RE = /\.(jpe?g|png|webp|gif)$/i;
const SAFE_FILENAME_RE = /^[a-zA-Z0-9._-]+$/;
/* Liste canonique des formats d'image acceptés dans tout l'admin — seule
   source de vérité pour filtrer les fichiers choisis par l'utilisateur
   (sélecteur système, glisser-déposer). DOIT rester strictement alignée
   sur ALLOWED_IMAGE_EXT dans inc/config.php : ce sont les seuls formats
   que le serveur sait vraiment décoder (momox_verify_image_content() n'a
   pas de décodeur GD pour SVG/AVIF, il ne fait que reconnaître ces
   formats en contenu pour donner un message d'erreur plus clair). Un
   format accepté ici mais refusé côté serveur serait pire qu'utile : le
   fichier passerait tout le flux (aperçu, renommage, confirmation) pour
   échouer seulement à la toute fin avec un message générique. Avant,
   chaque zone de dépôt avait en plus sa propre petite regex ad hoc,
   souvent trop permissive dans l'autre sens (acceptant tout type MIME
   "image/*", y compris HEIC/TIFF qu'un <img> ne sait pas afficher). */
const IMAGE_ACCEPT_ATTR = 'image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif';
const IMAGE_ACCEPT_HINT = 'Choisis des fichiers image (.jpg, .jpeg, .png, .webp ou .gif).';
/* DOIT rester alignée sur MAX_UPLOAD_SIZE dans inc/config.php (10 Mo).
   Un contrôle uniquement côté serveur obligerait à envoyer le fichier en
   entier — potentiellement plusieurs Mo sur une connexion lente — pour
   apprendre seulement à la fin qu'il est trop gros. */
const MAX_UPLOAD_SIZE_BYTES = 10 * 1024 * 1024;
function imageSizeErrorMessage(file){
  if (!file || file.size <= MAX_UPLOAD_SIZE_BYTES) return null;
  return `« ${file.name} » fait ${formatFileSize(file.size)} — la limite autorisée par le serveur est de ${(MAX_UPLOAD_SIZE_BYTES / 1024 / 1024).toFixed(1)} Mo. Réduis-la (ex. squoosh.app) avant de l'envoyer.`;
}
function isAcceptableImageFile(file){ return IMAGE_EXT_RE.test((file && file.name) || ''); }
function filterAcceptableImageFiles(fileList){ return Array.from(fileList || []).filter(isAcceptableImageFile); }
/* Sépare une sélection de fichiers déjà filtrés par extension entre ceux
   qui passeront la limite de taille du serveur (inc/config.php,
   MAX_UPLOAD_SIZE) et ceux qui seront rejetés à coup sûr. Utilisé pour
   prévenir tout de suite plutôt que de laisser l'utilisateur attendre la
   fin d'un envoi (potentiellement long sur une connexion lente) pour
   apprendre que le fichier était trop gros. */
function splitOversizedFiles(files){
  const ok = [], oversized = [];
  (files || []).forEach(f => (f.size > MAX_UPLOAD_SIZE_BYTES ? oversized : ok).push(f));
  return { ok, oversized };
}
function warnOversizedFiles(oversized){
  if (!oversized.length) return;
  const maxMo = (MAX_UPLOAD_SIZE_BYTES / 1024 / 1024).toFixed(1);
  const names = oversized.map(f => `« ${f.name} » (${formatFileSize(f.size)})`).join(', ');
  showToast(
    oversized.length === 1
      ? `${names} dépasse la taille maximale autorisée (${maxMo} Mo) — non ajoutée.`
      : `${oversized.length} fichiers dépassent ${maxMo} Mo et n'ont pas été ajoutés : ${names}.`,
    true
  );
}
/* Emplacement neutre affiché à la place d'une image qui échoue au
   chargement (fichier introuvable sur le serveur, ou format que ce
   navigateur ne sait pas décoder — HEIC, TIFF, SVG mal formé…) : un
   simple SVG inline (data URI) plutôt qu'un fichier externe, pour qu'il
   s'affiche toujours, même si le problème vient du réseau. Remplace le
   cadre brisé natif du navigateur par quelque chose de neutre et lisible,
   quel que soit le format/l'extension/l'encodage/la taille du fichier
   d'origine — utilisé par tous les aperçus miniatures de cet admin.
   `dataset.fallbackApplied` évite une boucle infinie si jamais ce data
   URI lui-même échouait à charger (ne devrait jamais arriver). */
const IMG_FALLBACK_SRC = 'data:image/svg+xml;utf8,' + encodeURIComponent(
  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">' +
  '<rect width="100" height="100" fill="#f4ecd8"/>' +
  '<text x="50" y="60" font-size="36" text-anchor="middle">\uD83D\uDDBC\uFE0F</text>' +
  '</svg>'
);
function useImgFallback(imgEl){
  if (imgEl.dataset.fallbackApplied) return;
  imgEl.dataset.fallbackApplied = '1';
  imgEl.classList.add('is-broken');
  imgEl.src = IMG_FALLBACK_SRC;
}

/* Numéro français uniquement (le site cible une clientèle locale, zone
   95/92/78) : soit 10 chiffres commençant par 0 (ex. 06 12 34 56 78),
   soit le même numéro en format international avec l'indicatif 33 (avec
   ou sans le "00" devant — le "+" est de toute façon retiré avant ce
   test). L'ancienne règle acceptait presque n'importe quelle suite de
   6 à 15 chiffres, y compris des numéros qui n'ont pas la forme d'un
   vrai numéro de téléphone. */
function isValidPhone(v){
  if (!/^[0-9+()\-.\s]+$/.test(v)) return false;
  const digits = v.replace(/\D/g, '');
  if (/^0\d{9}$/.test(digits)) return true;
  if (/^(0033|33)\d{9}$/.test(digits)) return true;
  return false;
}

/* Affiche/efface le message d'erreur juste sous un champ. Réutilise la
   div .field-error déjà présente si l'on revalide plusieurs fois. */
/* `anchorEl` : l'élément après lequel insérer le message d'erreur dans
   le DOM. Par défaut, c'est le champ lui-même ; pour un champ composé
   (ex. dossier forcé + nom de fichier dans une rangée flex), on passe
   plutôt la rangée entière pour ne pas casser sa mise en page. */
function setFieldError(el, message, anchorEl){
  anchorEl = anchorEl || el;
  let holder = anchorEl.nextElementSibling;
  if (!holder || !holder.classList || !holder.classList.contains('field-error')){
    holder = document.createElement('div');
    holder.className = 'field-error';
    anchorEl.insertAdjacentElement('afterend', holder);
  }
  if (message){
    const wasInvalid = el.classList.contains('invalid');
    el.classList.add('invalid');
    el.classList.remove('valid');
    holder.textContent = message;
    holder.classList.add('show');
    // Tremblement uniquement au moment où l'erreur APPARAÎT (pas à
    // chaque frappe suivante tant qu'elle reste affichée) — sinon le
    // champ tremblerait sans arrêt pendant que la personne corrige.
    if (!wasInvalid){
      el.classList.remove('invalid-shake');
      void el.offsetWidth; // force le navigateur à "oublier" l'état précédent, pour pouvoir rejouer l'animation
      el.classList.add('invalid-shake');
    }
  } else {
    const wasValid = el.classList.contains('valid');
    el.classList.remove('invalid', 'invalid-shake');
    holder.textContent = '';
    holder.classList.remove('show');
    // Confirmation positive seulement si le champ contient effectivement
    // quelque chose — pas de coche sur un champ optionnel resté vide.
    const hasValue = !!(el.value || '').trim();
    if (hasValue){
      el.classList.add('valid');
      if (!wasValid){
        el.classList.remove('valid-pulse');
        void el.offsetWidth;
        el.classList.add('valid-pulse');
      }
    } else {
      el.classList.remove('valid', 'valid-pulse');
    }
  }
  return holder;
}

/* Relie un champ à une fonction de validation. La vérification se
   déclenche à la perte de focus (pour ne pas asséner des erreurs
   pendant la frappe), puis en direct dès que le champ a été « touché »
   une première fois, pour que l'erreur disparaisse aussitôt corrigée.
   La fonction renvoyée (poussée dans `bucket`) sert à la vérification
   groupée avant téléchargement : elle force alors l'affichage.
   `anchorEl` (facultatif) : voir setFieldError. */
function attachFieldValidation(el, computeMessage, bucket, anchorEl){
  let touched = false;
  function run(forceShow){
    const msg = computeMessage(el.value);
    if (forceShow || touched) setFieldError(el, msg, anchorEl);
    if (forceShow) touched = true;
    return { valid: !msg, el };
  }
  el.addEventListener('blur', () => run(true));
  el.addEventListener('input', () => { if (touched) run(true); });
  if (bucket) bucket.push(run);
  return run;
}

/* --------------------------- Chemins d'image forcés --------------------------- */
/* Sépare un chemin complet ("images/xxx.jpg") en nom de fichier affiché
   dans le champ, en retirant le dossier attendu s'il est bien présent.
   Si le chemin existant ne commence pas par le dossier attendu (donnée
   ancienne / déplacée), on le laisse tel quel dans le champ : la
   validation signalera alors qu'il faut n'indiquer qu'un nom de fichier,
   plutôt que d'écraser silencieusement une donnée existante. */
function splitImagePath(full, prefix){
  const v = (full || '').trim();
  if (!v) return '';
  if (v.startsWith(prefix)) return v.slice(prefix.length);
  return v;
}
/* Recompose le chemin complet à partir du nom de fichier saisi. Si
   l'utilisateur a quand même tapé un chemin avec un « / », on le garde
   tel quel (sans double préfixe) : la validation le signalera comme
   invalide tant qu'il n'aura pas été corrigé en simple nom de fichier. */
function composeImagePath(filenameRaw, prefix){
  const v = (filenameRaw || '').trim();
  if (!v) return '';
  if (v.includes('/') || v.includes('\\')) return v;
  return prefix + v;
}
/* Message d'erreur pour un champ « nom de fichier image ». `required`
   détermine si un champ vide est tout de même signalé. */
function validateImageFilename(value, required){
  const v = (value || '').trim();
  if (!v) return required ? 'Indique un nom de fichier image.' : null;
  if (v.includes('/') || v.includes('\\')) return 'N\u2019indique que le nom du fichier, sans dossier (ex. « chien.jpg ») — le dossier est ajouté automatiquement.';
  if (/\s/.test(v)) return 'Évite les espaces dans le nom du fichier — utilise des tirets, ex. « photo-chien-1.jpg ».';
  if (!IMAGE_EXT_RE.test(v)) return 'Le nom doit se terminer par une extension d\u2019image valide (.jpg, .jpeg, .png, .webp ou .gif).';
  const withoutExt = v.slice(0, v.lastIndexOf('.'));
  if (!withoutExt) return 'Il manque un nom avant l\u2019extension (ex. « chien.jpg », pas juste « .jpg »).';
  if (!SAFE_FILENAME_RE.test(v)) return 'Utilise uniquement des lettres non accentuées, chiffres, tirets ( - _ ) et un point avant l\u2019extension.';
  return null;
}

/* Construit un champ « nom de fichier » avec dossier forcé affiché en
   préfixe, un aperçu image, et le branchement à CONTENT. Renvoie un
   objet avec refresh()/validate() pour l'appelant. */
function buildImageFilenameField(wrapEl, { prefix, getFull, setFull, onCommit, imgEl, fallbackEl, required=false, bucket }){
  const row = document.createElement('div');
  row.className = 'filepath-row';
  const input = document.createElement('input');
  input.type = 'text';
  input.spellcheck = false;
  input.placeholder = 'nom-de-fichier.jpg';
  const prefixSpan = document.createElement('span');
  prefixSpan.className = 'filepath-prefix';
  prefixSpan.textContent = prefix;
  row.appendChild(prefixSpan);
  row.appendChild(input);
  wrapEl.appendChild(row);

  // Bouton « Parcourir » — ouvre la modale de sélection parmi les
  // photos déjà présentes sur le serveur (images/ et images/galerie/).
  const browseBtn = document.createElement('button');
  browseBtn.type = 'button';
  browseBtn.className = 'filepath-browse-btn';
  browseBtn.title = 'Choisir une image déjà sur le serveur';
  browseBtn.setAttribute('aria-label', 'Choisir une image déjà sur le serveur');
  browseBtn.textContent = '🖼️';
  wrapEl.appendChild(browseBtn);

  // Bouton « Envoyer une nouvelle photo » — ouvre directement le sélecteur
  // de fichiers de l'appareil (pas besoin de taper un nom avant) : une fois
  // la photo choisie, un nom de fichier valide et disponible est généré
  // automatiquement à partir de son nom d'origine, puis vient remplacer le
  // contenu du champ. Permet de fournir la photo sans passer par le panneau
  // « Envoyer des photos » séparé, directement là où elle est utilisée.
  const uploadBtn = document.createElement('button');
  uploadBtn.type = 'button';
  uploadBtn.className = 'filepath-upload-btn';
  uploadBtn.title = 'Choisir une nouvelle photo depuis l\u2019appareil (le nom du fichier est généré automatiquement)';
  uploadBtn.setAttribute('aria-label', 'Choisir une nouvelle photo depuis l\u2019appareil');
  uploadBtn.textContent = '📷';
  wrapEl.appendChild(uploadBtn);

  const uploadFileInput = document.createElement('input');
  uploadFileInput.type = 'file';
  uploadFileInput.accept = IMAGE_ACCEPT_ATTR;
  uploadFileInput.style.cssText = 'position:absolute; width:1px; height:1px; opacity:0; pointer-events:none;';
  wrapEl.appendChild(uploadFileInput);

  uploadBtn.addEventListener('click', () => {
    uploadFileInput.click();
  });

  uploadFileInput.addEventListener('change', async () => {
    const file = uploadFileInput.files[0];
    uploadFileInput.value = '';
    if (!file) return;

    const ext = fileExt(file.name);
    if (!ext || !IMAGE_EXT_RE.test('x.' + ext)){
      showToast('Ce fichier n\u2019a pas une extension d\u2019image reconnue (.jpg, .jpeg, .png, .webp ou .gif).', true);
      return;
    }
    const sizeError = imageSizeErrorMessage(file);
    if (sizeError){
      showToast(sizeError, true);
      return;
    }

    // Nom de fichier proposé par défaut à partir du nom d'origine (accents/
    // espaces/majuscules nettoyés), rendu unique sur le serveur en cas de
    // doublon (ex. plusieurs photos "IMG_0001.jpg" prises au même endroit).
    // Il reste modifiable dans la modale de confirmation qui suit.
    const targetBucket = bucketFromPrefix(prefix);
    await loadServerImages(true); // liste fraîche, le cache local a pu devenir obsolète
    const base = slugifyBase(file.name) || 'photo';
    let suggested = base + '.' + ext;
    let n = 2;
    while (isNameTakenSync(targetBucket, suggested)){
      suggested = base + '-' + n + '.' + ext;
      n++;
    }

    // Confirmation du nom avant envoi, avec aperçu de la photo choisie
    // pour qu'il reste évident à quoi ce nom va correspondre. Le nom
    // choisi est systématiquement revérifié par rapport aux images déjà
    // existantes sur le serveur (sauf celle déjà utilisée par ce champ).
    const candidate = await askUploadFilename({ file, prefix, suggested, currentFull: getFull() });
    if (!candidate) return; // annulé

    const pwResult = await askPublishPassword({
      icon: '📷',
      title: 'Confirme l\u2019envoi',
      message: 'Pour envoyer « ' + candidate + ' » sur le serveur, ressaisis le mot de passe admin.',
      okLabel: '⬆ Envoyer'
    });
    if (!pwResult.ok) return;

    const originalLabel = uploadBtn.textContent;
    uploadBtn.disabled = true;
    uploadBtn.textContent = '…';
    try {
      const targetPath = prefix + candidate;
      const form = new FormData();
      form.append('photo', file, candidate);
      form.append('target_path', targetPath);
      form.append('confirm_password', pwResult.password);
      const res = await fetchWithTimeout('inc/upload-image.php', { method:'POST', credentials:'same-origin', body: form }, 90000);
      const data = await readJsonSafe(res);
      if (res.status === 401){
        showToast('Session expirée — recharge la page.', true);
      } else if (!res.ok || !data || !data.ok){
        showToast((data && data.message) || 'Échec de l\u2019envoi de la photo.', true);
      } else {
        input.value = candidate; // remplace le champ par le nouveau nom généré
        setFull(targetPath);
        refreshPreview();
        runValidation(true);
        onCommit();
        flushHistoryDebounce();
        invalidateServerImages();
        invalidateUnusedPhotos();
        showToast('Photo envoyée et associée à ce champ sous « ' + candidate + ' ».');
      }
    } catch(e){
      showToast(e.message || 'Erreur réseau pendant l\u2019envoi.', true);
    } finally {
      uploadBtn.disabled = false;
      uploadBtn.textContent = originalLabel;
    }
  });

  input.value = splitImagePath(getFull(), prefix);

  function refreshPreview(){
    const full = getFull();
    if (!imgEl) return;
    if (!full){ imgEl.style.display='none'; if (fallbackEl){ fallbackEl.style.display='flex'; fallbackEl.textContent = 'Aucune image renseignée'; } return; }
    imgEl.src = full;
    imgEl.style.display = 'block';
    if (fallbackEl) fallbackEl.style.display = 'none';
    imgEl.onerror = () => { imgEl.style.display='none'; if (fallbackEl){ fallbackEl.style.display='flex'; fallbackEl.textContent = 'Aperçu indisponible ici (' + full + ') — sera visible sur le site en ligne si le fichier existe à cet endroit'; } };
  }
  refreshPreview();

  input.oninput = () => {
    setFull(composeImagePath(input.value, prefix));
    refreshPreview();
    onCommit();
  };
  const runValidation = attachFieldValidation(input, (v) => validateImageFilename(v, required), bucket, row);
  input.addEventListener('blur', flushHistoryDebounce);

  browseBtn.addEventListener('click', async () => {
    const chosenFull = await openImagePicker(prefix);
    if (!chosenFull) return;
    input.value = splitImagePath(chosenFull, prefix);
    setFull(chosenFull);
    refreshPreview();
    runValidation(true);
    onCommit();
    flushHistoryDebounce();
  });

  return { refreshPreview, runValidation };
}

/* =====================================================================
   SÉLECTEUR DE POINT FOCAL (cadrage d'un cours)
   Un exemple concret de rendu plutôt qu'un champ texte abstrait : deux
   aperçus, chacun au ratio réel du format concerné sur le site (bande
   large sur mobile, colonne haute sur grand écran), recadrés en direct
   avec la vraie photo (background-size:cover se comporte exactement
   comme le object-fit:cover utilisé sur le site). Cliquer ou glisser
   dans l'aperçu déplace le point de cadrage ; sans réglage, le centre
   de la photo est utilisé par défaut (comportement identique à avant
   l'ajout de ce champ, donc un content.json plus ancien ou modifié à
   la main sans ces champs s'affiche normalement, juste centré).
   Stocke une valeur "X% Y%" (ex. "62% 20%") directement utilisable
   comme object-position, ou "" pour "centré (par défaut)".

   Aperçu MOBILE — ratio dynamique : sur le site, la fiche mobile n'a
   plus une bande de hauteur fixe : sa hauteur suit le ratio réel de
   CHAQUE photo (aspect-ratio:var(--photo-ratio)), avec un plancher et
   un plafond exprimés en vh (voir .modal-media dans index.html). Une
   photo large obtient une bande basse, une photo verticale une bande
   haute — jusqu'à ces limites. On reproduit ici exactement la même
   formule (sur un téléphone de référence 390×844, le format le plus
   courant) pour que l'aperçu admin montre l'EXACT rendu du site plutôt
   qu'un ratio unique approximatif comme avant. Tant que ce plancher/
   plafond n'est pas atteint, la photo tient entière, sans aucun
   recadrage — l'indice sous l'aperçu le confirme en direct.
   ===================================================================== */
const MOBILE_REF_VIEWPORT = { width: 390, height: 844 }; // téléphone de référence (format le plus courant)
const MOBILE_MEDIA_SIDE_PADDING = 16; // padding gauche/droite de la modale, cf. .modal-overlay dans index.html
const MOBILE_MEDIA_MIN_H = 190;       // cf. .modal-media min-height dans index.html
const MOBILE_MEDIA_MAX_VH = 0.40;     // cf. .modal-media max-height:min(40vh,…) dans index.html
const MOBILE_MEDIA_MAX_PX = 560;      // cf. .modal-media max-height:min(…,560px) dans index.html
const DESKTOP_BOX_RATIO = 31 / 60;    // cf. .focal-picker-box.is-desktop, ratio fixe de la colonne grand écran

function computeMobileMediaMetrics(naturalW, naturalH){
  const containerW = MOBILE_REF_VIEWPORT.width - MOBILE_MEDIA_SIDE_PADDING * 2;
  const idealH = containerW * (naturalH / naturalW);
  const maxH = Math.min(MOBILE_REF_VIEWPORT.height * MOBILE_MEDIA_MAX_VH, MOBILE_MEDIA_MAX_PX);
  const finalH = Math.min(maxH, Math.max(MOBILE_MEDIA_MIN_H, idealH));
  const cropped = Math.abs(finalH - idealH) > 0.5;
  const visiblePct = Math.max(1, Math.min(100, Math.round((finalH / idealH) * 100)));
  return { ratio: containerW / finalH, finalH: Math.round(finalH), cropped, visiblePct };
}

/* Même principe que computeMobileMediaMetrics ci-dessus, mais pour le
   format grand écran : contrairement à la bande mobile (hauteur adaptée
   à CHAQUE photo), la colonne grand écran garde toujours le même ratio
   fixe (31/60, cf. .focal-picker-box.is-desktop) — la photo y est donc
   recadrée par object-fit:cover dès que son ratio naturel diffère,
   presque toujours en pratique. Calcule quelle proportion de la photo
   reste visible, pour un indice symétrique à celui du mobile. */
function computeDesktopCoverMetrics(naturalW, naturalH){
  const photoRatio = naturalW / naturalH;
  const cropped = Math.abs(photoRatio - DESKTOP_BOX_RATIO) > 0.01;
  const wider = photoRatio > DESKTOP_BOX_RATIO;
  const visiblePct = Math.max(1, Math.min(100, Math.round((wider ? DESKTOP_BOX_RATIO / photoRatio : photoRatio / DESKTOP_BOX_RATIO) * 100)));
  return { cropped, visiblePct, axis: wider ? 'largeur' : 'hauteur' };
}

// Cache des dimensions naturelles par URL d'image, partagé par les deux
// aperçus (mobile/desktop) et par la loupe de précision ci-dessous, pour
// éviter de recharger la photo à chaque frappe dans les autres champs du
// formulaire (paint() est appelée souvent).
const IMG_DIM_CACHE = new Map();
function loadImgDims(url){
  if (IMG_DIM_CACHE.has(url)) return Promise.resolve(IMG_DIM_CACHE.get(url));
  return new Promise((resolve, reject) => {
    const probe = new Image();
    probe.onload = () => { const d = [probe.naturalWidth, probe.naturalHeight]; IMG_DIM_CACHE.set(url, d); resolve(d); };
    probe.onerror = reject;
    probe.src = url;
  });
}

/* Préréglages de cadrage rapide façon « ancre de recadrage » (coins,
   bords, centre) — un clic pose directement le point sur l'une des 9
   positions les plus utiles en pratique (portrait centré haut, sujet à
   gauche…), sans avoir à viser précisément à la souris pour ces cas très
   courants. Le glisser reste disponible pour tout réglage plus fin. */
const FOCAL_PRESETS = [
  { x:0,   y:0,   t:'Haut-gauche' }, { x:50, y:0,   t:'Haut' },   { x:100, y:0,   t:'Haut-droit' },
  { x:0,   y:50,  t:'Gauche' },      { x:50, y:50,  t:'Centre' }, { x:100, y:50,  t:'Droite' },
  { x:0,   y:100, t:'Bas-gauche' },  { x:50, y:100, t:'Bas' },    { x:100, y:100, t:'Bas-droit' },
];
const FOCAL_LOUPE_ZOOM = 3; // grossissement de la loupe de précision pendant le glisser

function buildFocalPointField(wrapEl, { variant, label, tag, desc, getImageUrl, getValue, setValue, onCommit, onSyncTo, syncLabel }){
  wrapEl.innerHTML = `
    <div class="focal-picker-label"><span>${label}</span><span class="tag">${tag}</span></div>
    <div class="focal-picker-desc">${desc}</div>
    <div class="focal-picker-box is-${variant}" tabindex="0" role="slider" aria-label="${label} — cliquer, glisser ou utiliser les flèches pour déplacer le point de cadrage">
      <div class="focal-picker-grid" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
      <div class="focal-picker-loupe" aria-hidden="true"></div>
      <div class="focal-picker-dot"></div>
    </div>
    <div class="focal-picker-presets" role="group" aria-label="Cadrages rapides">
      ${FOCAL_PRESETS.map(p => `<button type="button" class="focal-preset-btn" data-x="${p.x}" data-y="${p.y}" title="${p.t}" aria-label="${p.t}"></button>`).join('')}
    </div>
    <div class="focal-picker-foot">
      <span class="focal-picker-coords"></span>
      <span class="focal-picker-actions">
        ${onSyncTo ? `<button type="button" class="focal-picker-sync" title="Copier ce cadrage ${syncLabel}">⇄ ${syncLabel}</button>` : ''}
        <button type="button" class="focal-picker-reset">↺ Centrer (défaut)</button>
      </span>
    </div>
    <div class="focal-picker-hint is-empty"><span class="dot"></span><span data-hint-text></span></div>
  `;
  const box = wrapEl.querySelector('.focal-picker-box');
  const dot = wrapEl.querySelector('.focal-picker-dot');
  const loupe = wrapEl.querySelector('.focal-picker-loupe');
  const coordsEl = wrapEl.querySelector('.focal-picker-coords');
  const resetBtn = wrapEl.querySelector('.focal-picker-reset');
  const syncBtn = wrapEl.querySelector('.focal-picker-sync');
  const hintEl = wrapEl.querySelector('.focal-picker-hint');
  const hintTextEl = wrapEl.querySelector('[data-hint-text]');
  const presetBtns = $$('.focal-preset-btn', wrapEl);
  let lastRatioUrl = null;
  let lastDims = null; // [naturalW, naturalH] de la photo actuelle, pour la loupe

  // Recalcule l'indice "photo entière / recadrée" depuis les vraies
  // dimensions de la photo (chargées une seule fois par URL, cf. cache
  // ci-dessus) : ratio dynamique + indice pour le mobile, indice seul
  // (ratio fixe) pour le grand écran — symétrique des deux côtés.
  function updateCropHint(url){
    if (!url){
      if (variant === 'mobile') box.style.aspectRatio = '';
      hintEl.classList.add('is-empty');
      lastRatioUrl = null; lastDims = null;
      return;
    }
    if (url === lastRatioUrl) return;
    lastRatioUrl = url;
    loadImgDims(url).then(([natW, natH]) => {
      if (url !== getImageUrl()) return; // l'image a changé entretemps
      lastDims = [natW, natH];
      if (variant === 'mobile'){
        const m = computeMobileMediaMetrics(natW, natH);
        box.style.aspectRatio = String(m.ratio);
        hintEl.classList.remove('is-empty');
        hintEl.classList.toggle('is-cropped', m.cropped);
        hintTextEl.textContent = m.cropped
          ? `≈ ${m.visiblePct}% de la photo visible (bande d'environ ${m.finalH}px sur téléphone standard) — le reste est recadré`
          : `Photo entière visible, sans recadrage (bande d'environ ${m.finalH}px sur téléphone standard)`;
      } else {
        const m = computeDesktopCoverMetrics(natW, natH);
        hintEl.classList.remove('is-empty');
        hintEl.classList.toggle('is-cropped', m.cropped);
        hintTextEl.textContent = m.cropped
          ? `≈ ${m.visiblePct}% de la ${m.axis} de la photo visible dans la colonne — le reste est recadré, le point choisit quelle partie`
          : `Photo entière visible, sans recadrage sur ce format`;
      }
    }).catch(() => { lastDims = null; });
  }

  function parsePos(v){
    const m = /^(-?\d+(?:\.\d+)?)%\s+(-?\d+(?:\.\d+)?)%$/.exec((v || '').trim());
    if (!m) return { x:50, y:50 };
    return { x: Math.min(100, Math.max(0, parseFloat(m[1]))), y: Math.min(100, Math.max(0, parseFloat(m[2]))) };
  }

  function paint(){
    const url = getImageUrl();
    box.classList.toggle('is-empty', !url);
    box.style.backgroundImage = url ? `url("${url}")` : 'none';
    updateCropHint(url);
    const raw = getValue();
    const { x, y } = parsePos(raw);
    box.style.backgroundPosition = x + '% ' + y + '%';
    dot.style.left = x + '%';
    dot.style.top = y + '%';
    box.setAttribute('aria-valuetext', raw ? `${Math.round(x)}% / ${Math.round(y)}%` : 'Centré');
    coordsEl.textContent = raw ? Math.round(x) + '% / ' + Math.round(y) + '%' : 'Centré (par défaut)';
    resetBtn.disabled = !raw;
    resetBtn.style.visibility = raw ? 'visible' : 'hidden';
    if (syncBtn) syncBtn.disabled = box.classList.contains('is-empty');
    presetBtns.forEach(btn => {
      const isActive = !!raw && Math.round(x) === Number(btn.dataset.x) && Math.round(y) === Number(btn.dataset.y);
      btn.classList.toggle('is-active', isActive);
    });
  }

  // Petit flash doré sur le point au moment où une position est posée
  // (clic, glisser relâché, préréglage, flèche clavier, reset, sync) —
  // confirmation visuelle immédiate, se relance à chaque déclenchement
  // même répété plutôt que de rester bloquée sur la première fois.
  function pulseDot(){
    dot.classList.remove('is-pulsing');
    void dot.offsetWidth; // force le redémarrage de l'animation CSS
    dot.classList.add('is-pulsing');
  }

  // Positionne la loupe de précision juste au-dessus du doigt/curseur et
  // affiche un zoom de la photo ENTIÈRE (pas de l'aperçu recadré) centré
  // exactement sur le point visé — utile pour caler précisément un petit
  // détail (regard d'un chien, étiquette…) qu'un point sur un aperçu déjà
  // recadré rendrait difficile à distinguer.
  function updateLoupe(clientX, clientY, xPct, yPct){
    if (!lastDims){ loupe.style.opacity = '0'; return; }
    const rect = box.getBoundingClientRect();
    const [natW, natH] = lastDims;
    const loupeSize = loupe.offsetWidth || 64;
    const zw = natW * FOCAL_LOUPE_ZOOM, zh = natH * FOCAL_LOUPE_ZOOM;
    loupe.style.backgroundImage = box.style.backgroundImage;
    loupe.style.backgroundSize = zw + 'px ' + zh + 'px';
    loupe.style.backgroundPosition = (loupeSize / 2 - (xPct / 100) * zw) + 'px ' + (loupeSize / 2 - (yPct / 100) * zh) + 'px';
    loupe.style.left = (clientX - rect.left) + 'px';
    loupe.style.top = (clientY - rect.top) + 'px';
  }

  function setFromClient(clientX, clientY){
    const rect = box.getBoundingClientRect();
    if (!rect.width || !rect.height) return;
    const x = Math.min(100, Math.max(0, ((clientX - rect.left) / rect.width) * 100));
    const y = Math.min(100, Math.max(0, ((clientY - rect.top) / rect.height) * 100));
    setValue(Math.round(x) + '% ' + Math.round(y) + '%');
    paint();
    updateLoupe(clientX, clientY, x, y);
    onCommit({ debounce:true });
  }

  let dragging = false;
  box.addEventListener('pointerdown', (e) => {
    if (box.classList.contains('is-empty')) return;
    dragging = true;
    box.classList.remove('is-animated'); // glisser réel : jamais de transition, pour rester instantané
    box.classList.add('is-dragging');
    box.setPointerCapture(e.pointerId);
    setFromClient(e.clientX, e.clientY);
  });
  box.addEventListener('pointermove', (e) => { if (dragging) setFromClient(e.clientX, e.clientY); });
  const stopDrag = () => {
    if (!dragging) return;
    dragging = false;
    box.classList.remove('is-dragging');
    loupe.style.opacity = '0';
    pulseDot();
    flushHistoryDebounce();
  };
  box.addEventListener('pointerup', stopDrag);
  box.addEventListener('pointercancel', stopDrag);
  box.addEventListener('dblclick', () => {
    // Double-clic/double-tap : raccourci pour revenir au centrage par
    // défaut sans viser le bouton « Centrer » — pratique sur mobile.
    if (box.classList.contains('is-empty')) return;
    box.classList.add('is-animated');
    setValue('');
    paint();
    pulseDot();
    onCommit({ debounce:false });
    flushHistoryDebounce();
  });
  box.addEventListener('keydown', (e) => {
    if (box.classList.contains('is-empty')) return;
    const { x, y } = parsePos(getValue());
    const step = e.shiftKey ? 10 : 3;
    let nx = x, ny = y;
    if (e.key === 'ArrowLeft') nx -= step;
    else if (e.key === 'ArrowRight') nx += step;
    else if (e.key === 'ArrowUp') ny -= step;
    else if (e.key === 'ArrowDown') ny += step;
    else return;
    e.preventDefault();
    nx = Math.min(100, Math.max(0, nx)); ny = Math.min(100, Math.max(0, ny));
    box.classList.add('is-animated');
    setValue(Math.round(nx) + '% ' + Math.round(ny) + '%');
    paint();
    pulseDot();
    onCommit({ debounce:true });
    flushHistoryDebounce();
  });
  resetBtn.addEventListener('click', () => {
    box.classList.add('is-animated');
    setValue('');
    paint();
    pulseDot();
    onCommit({ debounce:false });
    flushHistoryDebounce();
  });
  presetBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      if (box.classList.contains('is-empty')) return;
      box.classList.add('is-animated');
      setValue(btn.dataset.x + '% ' + btn.dataset.y + '%');
      paint();
      pulseDot();
      onCommit({ debounce:false });
      flushHistoryDebounce();
    });
  });
  if (syncBtn){
    syncBtn.addEventListener('click', () => {
      if (box.classList.contains('is-empty')) return;
      onSyncTo(getValue());
      pulseDot();
    });
  }

  paint();
  return { refresh: paint };
}

/* =====================================================================
   SÉLECTEUR D'IMAGE SERVEUR (inc/list-images.php, inc/copy-image.php)
   Modale permettant de choisir une image déjà présente sur le serveur
   plutôt que de ressaisir un nom de fichier à l'aveugle. Une photo de
   la galerie peut être choisie pour une image principale et
   inversement : dans ce cas le fichier est copié dans le bon dossier
   (il peut exister dans les deux à la fois), après reconfirmation du
   mot de passe admin comme pour les autres actions qui touchent au
   serveur.
   ===================================================================== */
let SERVER_IMAGES = null;      // liste brute renvoyée par le serveur, mise en cache
let SERVER_IMAGES_STALE = true; // true tant qu'un envoi/copie n'a pas encore été reflété

function invalidateServerImages(){ SERVER_IMAGES_STALE = true; }

async function loadServerImages(force){
  if (SERVER_IMAGES && !SERVER_IMAGES_STALE && !force) return { ok:true, images: SERVER_IMAGES };
  try {
    const res = await fetchWithTimeout('inc/list-images.php', { credentials: 'same-origin' }, 20000);
    const data = await readJsonSafe(res);
    if (res.status === 401){
      return { ok:false, message: 'Session expirée — recharge la page.' };
    }
    if (!res.ok || !data || !data.ok){
      return { ok:false, message: (data && data.message) || 'Impossible de charger les photos du serveur.' };
    }
    SERVER_IMAGES = data.images || [];
    SERVER_IMAGES_STALE = false;
    return { ok:true, images: SERVER_IMAGES };
  } catch(e){
    return { ok:false, message: e.message || 'Erreur réseau pendant le chargement des photos.' };
  }
}

/* Dossier réel associé à un préfixe de champ ('images/' → 'images',
   'images/galerie/' → 'galerie'). */
function bucketFromPrefix(prefix){
  return prefix === 'images/galerie/' ? 'galerie' : 'images';
}

/* Vérifie (à partir du cache SERVER_IMAGES déjà chargé) si un nom de
   fichier existe déjà sur le serveur dans le bucket donné, sans requête
   réseau — utilisé pour bloquer un envoi *avant* qu'il ne parte, en plus
   du contrôle définitif fait côté serveur. Si le cache n'a encore jamais
   été chargé, ne bloque rien ici : le serveur refusera de toute façon un
   envoi en double (message « nom déjà pris »). */
function isNameTakenSync(bucket, name){
  if (!SERVER_IMAGES) return false;
  const n = String(name || '').toLowerCase();
  return SERVER_IMAGES.some(it => it.bucket === bucket && it.name.toLowerCase() === n);
}
// Amorce le chargement de la liste des photos serveur dès le départ, pour
// que les contrôles d'existence (panneaux d'envoi, champs image) aient
// une donnée à jour dès la première interaction plutôt que de tout
// autoriser en silence tant que la modale de sélection n'a pas été ouverte.
loadServerImages();

async function copyServerImage(sourcePath, targetBucket){
  const pwResult = await askPublishPassword({
    icon: '📎',
    title: 'Confirme la copie de cette photo',
    message: 'Cette photo va être copiée dans l\u2019autre dossier du serveur (elle restera aussi à son emplacement d\u2019origine) — ressaisis le mot de passe admin pour continuer.',
    okLabel: '📎 Copier la photo'
  });
  if (!pwResult.ok) return { ok:false, cancelled:true };
  try {
    const res = await fetchWithTimeout('inc/copy-image.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ source: sourcePath, target_bucket: targetBucket, confirm_password: pwResult.password })
    }, 30000);
    const data = await readJsonSafe(res);
    if (res.status === 401){
      return { ok:false, message: 'Session expirée — recharge la page.' };
    }
    if (!res.ok || !data || !data.ok){
      return { ok:false, message: (data && data.message) || 'Échec de la copie de la photo.' };
    }
    return { ok:true, path: data.path };
  } catch(e){
    return { ok:false, message: e.message || 'Erreur réseau pendant la copie.' };
  }
}

/* Ouvre la modale et renvoie une Promise résolue avec le chemin complet
   choisi (déjà dans le bon dossier, copié si nécessaire), ou null si
   l'utilisatrice a annulé. `targetPrefix` est le préfixe du champ
   d'origine ('images/' ou 'images/galerie/'). */
function openImagePicker(targetPrefix){
  const overlay = $('#imagePickerOverlay');
  const gridWrap = $('#ipGridWrap');
  const searchInput = $('#ipSearchInput');
  const crossNote = $('#ipCrossNote');
  const confirmBtn = $('#imagePickerConfirmBtn');
  const cancelBtn = $('#imagePickerCancelBtn');
  const tabsWrap = $('#ipTabs');
  const targetBucket = bucketFromPrefix(targetPrefix);

  return new Promise((resolve) => {
    let settled = false;
    let activeTab = 'all';
    let selected = null; // item choisi dans la grille
    let busy = false;
    const previouslyFocused = document.activeElement;

    function close(result){
      if (settled) return;
      settled = true;
      overlay.classList.remove('show');
      setTimeout(() => { overlay.style.display = 'none'; }, 260);
      document.removeEventListener('keydown', onKeydown, true);
      tabsWrap.querySelectorAll('[data-ip-tab]').forEach(b => b.removeEventListener('click', onTabClick));
      searchInput.removeEventListener('input', onSearch);
      confirmBtn.removeEventListener('click', onConfirm);
      cancelBtn.removeEventListener('click', onCancel);
      overlay.removeEventListener('click', onOverlayClick);
      if (previouslyFocused && typeof previouslyFocused.focus === 'function') previouslyFocused.focus();
      resolve(result);
    }
    function onCancel(){ if (!busy) close(null); }
    function onOverlayClick(e){ if (e.target === overlay) onCancel(); }
    function onKeydown(e){ if (e.key === 'Escape'){ e.preventDefault(); onCancel(); } }

    function updateCrossNote(){
      if (selected && selected.bucket !== targetBucket){
        const from = selected.bucket === 'galerie' ? 'la galerie' : 'les images principales';
        const to = targetBucket === 'galerie' ? 'la galerie' : 'les images principales';
        crossNote.style.display = 'flex';
        crossNote.innerHTML = '📎 Cette photo vient de ' + from + ' — elle sera <strong>copiée</strong> vers ' + to + ' pour être utilisée ici (elle restera aussi disponible dans ' + from + ').';
      } else {
        crossNote.style.display = 'none';
        crossNote.innerHTML = '';
      }
    }

    function renderGrid(){
      const items = (SERVER_IMAGES || []).filter(it => {
        if (activeTab !== 'all' && it.bucket !== activeTab) return false;
        const q = searchInput.value.trim().toLowerCase();
        if (q && !it.name.toLowerCase().includes(q)) return false;
        return true;
      });
      if (!items.length){
        gridWrap.innerHTML = '<div class="ip-empty">Aucune photo trouvée' + (searchInput.value.trim() ? ' pour cette recherche.' : ' sur le serveur.') + '</div>';
        return;
      }
      const grid = document.createElement('div');
      grid.className = 'ip-grid';
      items.forEach(it => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ip-item';
        if (selected && selected.path === it.path) btn.classList.add('is-selected');
        btn.innerHTML =
          '<img class="ip-item-thumb" src="' + escapeHtml(it.path) + '" alt="" loading="lazy" onerror="useImgFallback(this)">' +
          '<div class="ip-item-name">' + escapeHtml(it.name) +
            '<span class="ip-item-bucket">' + (it.bucket === 'galerie' ? 'Galerie' : 'Images') + '</span>' +
          '</div>';
        btn.addEventListener('click', () => {
          selected = it;
          confirmBtn.disabled = false;
          updateCrossNote();
          renderGrid();
        });
        grid.appendChild(btn);
      });
      gridWrap.innerHTML = '';
      gridWrap.appendChild(grid);
    }

    function onTabClick(e){
      activeTab = e.currentTarget.dataset.ipTab;
      tabsWrap.querySelectorAll('[data-ip-tab]').forEach(b => b.classList.toggle('is-active', b.dataset.ipTab === activeTab));
      renderGrid();
    }
    function onSearch(){ renderGrid(); }

    async function onConfirm(){
      if (!selected || busy) return;
      if (selected.bucket === targetBucket){
        close(selected.path);
        return;
      }
      busy = true;
      confirmBtn.disabled = true;
      cancelBtn.disabled = true;
      const originalLabel = confirmBtn.textContent;
      confirmBtn.textContent = 'Copie en cours…';
      const result = await copyServerImage(selected.path, targetBucket);
      busy = false;
      cancelBtn.disabled = false;
      confirmBtn.textContent = originalLabel;
      confirmBtn.disabled = false;
      if (result.cancelled) return; // mot de passe annulé : on reste sur la modale
      if (!result.ok){
        showToast(result.message || 'Échec de la copie de la photo.', true);
        return;
      }
      invalidateServerImages();
      close(result.path);
    }

    // Câblage
    tabsWrap.querySelectorAll('[data-ip-tab]').forEach(b => {
      b.classList.toggle('is-active', b.dataset.ipTab === 'all');
      b.addEventListener('click', onTabClick);
    });
    searchInput.value = '';
    searchInput.addEventListener('input', onSearch);
    confirmBtn.disabled = true;
    confirmBtn.addEventListener('click', onConfirm);
    cancelBtn.disabled = false;
    cancelBtn.addEventListener('click', onCancel);
    overlay.addEventListener('click', onOverlayClick);
    document.addEventListener('keydown', onKeydown, true);
    crossNote.style.display = 'none';

    overlay.style.display = 'flex';
    requestAnimationFrame(() => overlay.classList.add('show'));

    gridWrap.innerHTML = '<div class="ip-loading">Chargement des photos du serveur…</div>';
    loadServerImages().then(res => {
      if (settled) return;
      if (!res.ok){
        gridWrap.innerHTML = '<div class="ip-empty">⚠️ ' + escapeHtml(res.message || 'Impossible de charger les photos.') + '</div>';
        return;
      }
      renderGrid();
    });
  });
}

/* =====================================================================
   CONFIRMATION DU NOM AVANT ENVOI D'UNE NOUVELLE PHOTO
   Ouverte juste après le choix d'un fichier via le bouton 📷 sur
   n'importe quel champ image (Prestations, Images principales,
   Galerie...). Un aperçu de la photo tout juste choisie (+ son nom
   d'origine) reste affiché pendant que le nom proposé — généré
   automatiquement, mais modifiable — est ajusté, pour qu'il n'y ait
   jamais de doute sur la photo à laquelle ce nom va correspondre une
   fois envoyée. Fonction unique réutilisée par buildImageFilenameField,
   donc valable pour tous les champs image du site sans dupliquer la
   logique. Renvoie le nom final choisi, ou null si annulé.
   ===================================================================== */
function askUploadFilename({ file, prefix, suggested, currentFull }){
  const overlay    = $('#uploadNameOverlay');
  const modal      = $('#uploadNameModal');
  const photoEl    = $('#uploadNamePhoto');
  const origNameEl = $('#uploadNameOriginalName');
  const prefixEl   = $('#uploadNamePrefix');
  const input      = $('#uploadNameInput');
  const okBtn      = $('#uploadNameOkBtn');
  const cancelBtn  = $('#uploadNameCancelBtn');
  const form       = $('#uploadNameForm');
  const targetBucket = bucketFromPrefix(prefix);
  const originalExt = fileExt(file.name);
  // Nom actuellement utilisé par CE champ (s'il y en a un) : seul cas où
  // retomber sur un nom "déjà pris" est légitime (remplacer sa propre
  // photo en conservant son nom), donc exclu du contrôle de doublon.
  const ownCurrentName = (splitImagePath(currentFull || '', prefix) || '').toLowerCase();

  // Aperçu de la photo réellement choisie (pas une icône générique) :
  // c'est ce qui garantit qu'on sait toujours à quoi le nom correspond.
  // Si ce navigateur ne sait pas décoder ce fichier (format exotique,
  // fichier corrompu…), l'envoi porte toujours sur le fichier d'origine
  // intact — seule cette vignette est affectée, donc on bascule sur une
  // icône neutre plutôt que de laisser le cadre brisé natif.
  const objectUrl = URL.createObjectURL(file);
  photoEl.classList.remove('is-broken');
  delete photoEl.dataset.fallbackApplied;
  photoEl.onerror = () => useImgFallback(photoEl);
  photoEl.src = objectUrl;
  photoEl.alt = file.name;
  origNameEl.textContent = file.name;
  prefixEl.textContent = prefix;
  input.value = suggested;
  const previouslyFocused = document.activeElement;

  return new Promise((resolve) => {
    let settled = false;

    function close(result){
      if (settled) return;
      settled = true;
      overlay.classList.remove('show');
      setTimeout(() => { overlay.style.display = 'none'; URL.revokeObjectURL(objectUrl); }, 260);
      document.removeEventListener('keydown', onKeydown, true);
      okBtn.removeEventListener('click', onOk);
      cancelBtn.removeEventListener('click', onCancel);
      overlay.removeEventListener('click', onOverlayClick);
      form.removeEventListener('submit', onSubmit);
      input.removeEventListener('input', onInput);
      if (previouslyFocused && typeof previouslyFocused.focus === 'function') previouslyFocused.focus();
      resolve(result);
    }

    // Validation identique à celle des autres champs image (nom sûr,
    // extension d'image valide), plus deux règles propres à l'envoi :
    // garder la même extension que le fichier réellement choisi (pour ne
    // pas envoyer un .png sous un nom en .jpg), et ne jamais écraser en
    // silence une image déjà existante sur le serveur — sauf si c'est
    // celle déjà utilisée par ce champ précis (remplacement volontaire).
    function computeError(v){
      const base = validateImageFilename(v, true);
      if (base) return base;
      const wantExt = fileExt(v);
      if (originalExt && wantExt && wantExt.toLowerCase() !== originalExt.toLowerCase()){
        return 'Garde l\u2019extension .' + originalExt + ' du fichier choisi — ne renomme que la partie avant le point.';
      }
      if (v.toLowerCase() !== ownCurrentName && isNameTakenSync(targetBucket, v)){
        return 'Le nom « ' + v + ' » correspond à une image déjà existante sur le serveur — choisis-en un autre (ou utilise 🖼️ pour la sélectionner).';
      }
      return null;
    }

    function onInput(){
      const err = computeError(input.value.trim());
      setFieldError(input, err, input.closest('.filepath-row'));
      okBtn.disabled = !!err;
    }

    // Contrôle immédiat à l'ouverture : ne fait jamais confiance au nom
    // proposé par l'appelant sans le revérifier (la liste serveur a pu
    // changer entre-temps, ex. onglet ouvert ailleurs).
    onInput();

    function onOk(){
      const v = input.value.trim();
      const err = computeError(v);
      if (err){
        setFieldError(input, err, input.closest('.filepath-row'));
        modal.classList.remove('is-shake');
        void modal.offsetWidth; // relance l'animation même si elle vient de jouer
        modal.classList.add('is-shake');
        input.focus();
        return;
      }
      close(v);
    }
    function onCancel(){ close(null); }
    function onSubmit(e){ e.preventDefault(); if (!okBtn.disabled) onOk(); }
    function onOverlayClick(e){ if (e.target === overlay) onCancel(); }
    function onKeydown(e){ if (e.key === 'Escape'){ e.preventDefault(); onCancel(); } }

    form.addEventListener('submit', onSubmit);
    okBtn.addEventListener('click', onOk);
    cancelBtn.addEventListener('click', onCancel);
    overlay.addEventListener('click', onOverlayClick);
    input.addEventListener('input', onInput);
    document.addEventListener('keydown', onKeydown, true);

    overlay.style.display = 'flex';
    requestAnimationFrame(() => {
      overlay.classList.add('show');
      input.focus();
      // Sélectionne juste le nom (sans l'extension) pour que taper
      // remplace tout de suite la partie utile, comme un "renommer".
      const dot = suggested.lastIndexOf('.');
      if (dot > 0) input.setSelectionRange(0, dot); else input.select();
    });
  });
}

/* Ouvre le panneau contenant un champ, le fait défiler à l'écran et lui
   donne le focus — utilisé pour amener l'utilisateur au premier champ
   en erreur avant le téléchargement. */
function revealField(el){
  const panel = el.closest('[data-panel]');
  if (panel && !panel.classList.contains('open')) panel.classList.add('open');
  const serviceCard = el.closest('.service-card');
  if (serviceCard && !serviceCard.classList.contains('open')) setServiceCardOpen(serviceCard, true, false);
  if (typeof el.scrollIntoView === 'function') el.scrollIntoView({ behavior:'smooth', block:'center' });
  try { el.focus({ preventScroll:true }); } catch(e){ try { el.focus(); } catch(e2){} }
}

/* Relance la validation de TOUS les champs enregistrés (tous en mode
   "forceShow" pour que chaque erreur s'affiche), et renvoie le nombre
   d'erreurs ainsi que le premier champ fautif. */
function validateAll(){
  let invalidCount = 0;
  let firstInvalidEl = null;
  Object.values(SECTION_VALIDATORS).forEach(bucket => {
    (bucket || []).forEach(run => {
      const res = run(true);
      if (!res.valid){
        invalidCount++;
        if (!firstInvalidEl) firstInvalidEl = res.el;
      }
    });
  });
  return { ok: invalidCount === 0, invalidCount, firstInvalidEl };
}

/* Nombre réel de changements en attente : compare CONTENT à
   PRISTINE_CONTENT (la version réellement en ligne), indépendamment
   du fait qu'une action ait été effectuée récemment (annuler/rétablir,
   restauration d'une section...). C'est ce nombre qui doit gouverner
   l'affichage "À jour" / "modifications non téléchargées", pas un
   simple drapeau "quelque chose a été touché". */
function getPendingChangeCount(){
  if (!PRISTINE_CONTENT || !CONTENT) return 0;
  try {
    return diffContent(PRISTINE_CONTENT, CONTENT).reduce((sum, s) => sum + s.rows.length, 0);
  } catch(e){
    return 0;
  }
}

function setDirty(v){
  dirty = v;
  const pill = $('#statusPill');
  const n = getPendingChangeCount();
  if (n > 0){
    pill.textContent = n === 1 ? '1 modification non téléchargée' : n + ' modifications non téléchargées';
    pill.className = 'status-pill dirty';
  } else {
    pill.textContent = 'À jour';
    pill.className = 'status-pill saved';
  }
}

let autosaveFailed = false;
function setAutosaveWarning(failed){
  autosaveFailed = failed;
  $('#autosaveWarn').style.display = failed ? 'flex' : 'none';
}

function markDirty(){
  setDirty(true);
  try {
    localStorage.setItem(DRAFT_KEY, JSON.stringify(CONTENT));
    if (autosaveFailed) setAutosaveWarning(false);
  } catch(e){
    if (!autosaveFailed){
      setAutosaveWarning(true);
      showToast('Sauvegarde locale automatique impossible sur cet appareil (navigation privée ?) — télécharge ton fichier régulièrement pour ne rien perdre.', true);
    }
  }
}

window.addEventListener('beforeunload', (e) => {
  if (dirty){ e.preventDefault(); e.returnValue = ''; }
});

/* =====================================================================
   HISTORIQUE — ANNULER / RÉTABLIR
   Chaque action « significative » (champ modifié après une pause,
   ajout/suppression/réorganisation…) enregistre un instantané complet
   de CONTENT avec une description lisible. Les saisies au clavier sont
   regroupées (debounce) pour qu'un « Annuler » corresponde à une vraie
   étape plutôt qu'à une seule lettre tapée.
   ===================================================================== */
let HISTORY = [];
let HISTORY_INDEX = -1;
const HISTORY_LIMIT = 60;
const HISTORY_DEBOUNCE = 700;
let historyPendingLabel = null;
let historyDebounceTimer = null;

function snapshotContent(){
  return JSON.stringify(CONTENT);
}

function seedHistory(label){
  HISTORY = [{ label: label || 'État initial', state: snapshotContent() }];
  HISTORY_INDEX = 0;
  historyPendingLabel = null;
  clearTimeout(historyDebounceTimer);
  updateHistoryButtons();
}

function pushHistory(label){
  HISTORY = HISTORY.slice(0, HISTORY_INDEX + 1);
  HISTORY.push({ label, state: snapshotContent() });
  if (HISTORY.length > HISTORY_LIMIT) HISTORY.shift();
  HISTORY_INDEX = HISTORY.length - 1;
  updateHistoryButtons();
}

function flushHistoryDebounce(){
  if (historyDebounceTimer){
    clearTimeout(historyDebounceTimer);
    historyDebounceTimer = null;
    if (historyPendingLabel){
      pushHistory(historyPendingLabel);
      historyPendingLabel = null;
    }
  }
}

/* À appeler après CHAQUE modification de CONTENT, à la place de markDirty()
   seul. debounce:true pour les champs texte (regroupe la frappe). */
function commitChange(label, opts){
  const debounce = !!(opts && opts.debounce);
  markDirty();
  scheduleSectionBadgesUpdate(!debounce);
  if (debounce){
    historyPendingLabel = label;
    clearTimeout(historyDebounceTimer);
    historyDebounceTimer = setTimeout(() => {
      historyDebounceTimer = null;
      pushHistory(historyPendingLabel);
      historyPendingLabel = null;
    }, HISTORY_DEBOUNCE);
  } else {
    flushHistoryDebounce();
    pushHistory(label);
  }
}

function updateHistoryButtons(){
  const undoBtn = $('#undoBtn'), redoBtn = $('#redoBtn');
  if (!undoBtn || !redoBtn) return;
  const canUndo = HISTORY_INDEX > 0;
  const canRedo = HISTORY_INDEX < HISTORY.length - 1;
  undoBtn.disabled = !canUndo;
  redoBtn.disabled = !canRedo;
  undoBtn.title = canUndo ? ('Annuler : ' + HISTORY[HISTORY_INDEX].label) : 'Rien à annuler';
  redoBtn.title = canRedo ? ('Rétablir : ' + HISTORY[HISTORY_INDEX + 1].label) : 'Rien à rétablir';
}

function restoreHistoryState(index){
  CONTENT = JSON.parse(HISTORY[index].state);
  renderAll();
  applyTheme(CONTENT.theme);
  markDirty();
  updateHistoryButtons();
  scheduleSectionBadgesUpdate(true);
}

function undo(){
  flushHistoryDebounce();
  if (HISTORY_INDEX <= 0) return;
  const undoneLabel = HISTORY[HISTORY_INDEX].label;
  HISTORY_INDEX--;
  restoreHistoryState(HISTORY_INDEX);
  showActionToast('undo', undoneLabel);
}

function redo(){
  if (HISTORY_INDEX >= HISTORY.length - 1) return;
  HISTORY_INDEX++;
  restoreHistoryState(HISTORY_INDEX);
  showActionToast('redo', HISTORY[HISTORY_INDEX].label);
}

$('#undoBtn').addEventListener('click', undo);
$('#redoBtn').addEventListener('click', redo);

window.addEventListener('keydown', (e) => {
  const tag = document.activeElement && document.activeElement.tagName;
  const typing = tag === 'INPUT' || tag === 'TEXTAREA';
  if (!(e.ctrlKey || e.metaKey)) return;
  const key = e.key.toLowerCase();
  if (typing){
    // Dans un champ texte, on laisse le undo natif du navigateur agir sur
    // le texte ; on ne déclenche notre historique qu'une fois le champ quitté.
    return;
  }
  if (key === 'z' && !e.shiftKey){ e.preventDefault(); undo(); }
  else if ((key === 'z' && e.shiftKey) || key === 'y'){ e.preventDefault(); redo(); }
});

/* Toast riche et explicite pour Annuler / Rétablir */
function showActionToast(kind, label){
  const t = $('#toast');
  clearTimeout(showToast._timer);
  const isUndo = kind === 'undo';
  const iconPath = isUndo
    ? '<path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/>'
    : '<path d="M15 14l5-5-5-5"/><path d="M20 9H9.5a5.5 5.5 0 0 0 0 11H13"/>';
  t.innerHTML =
    '<div class="toast-row">' +
      '<div class="toast-icon ' + (isUndo ? 'undo' : 'redo') + '">' +
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">' + iconPath + '</svg>' +
      '</div>' +
      '<div class="toast-copy">' +
        '<div class="toast-title">' + (isUndo ? 'Annulé' : 'Rétabli') + '</div>' +
        '<div class="toast-desc">' + escapeHtml(label) + '</div>' +
      '</div>' +
    '</div>' +
    '<div class="toast-progress"><span></span></div>';
  t.className = 'toast show action-toast ' + (isUndo ? 'is-undo' : 'is-redo');
  const bar = t.querySelector('.toast-progress span');
  requestAnimationFrame(() => bar.classList.add('run'));
  showToast._timer = setTimeout(() => t.classList.remove('show'), 3100);
}

/* =====================================================================
   CHARGEMENT
   ===================================================================== */
async function init(){
  let loaded = null;
  let fetchError = null;
  try {
    const res = await fetchWithTimeout('content.json', { cache: 'no-store' }, 15000);
    if (res.ok){
      try {
        loaded = await res.json();
      } catch(parseErr){
        fetchError = new Error('content.json a été trouvé sur le serveur mais n\u2019est pas un JSON valide (' + parseErr.message + '). Corrige-le côté serveur, ou choisis un autre fichier ci-dessous.');
      }
    }
  } catch(e){
    // Timeout ou serveur injoignable — on affiche un message clair
    // plutôt que de laisser l'écran de chargement tourner indéfiniment.
    fetchError = new Error(e.message || "Le serveur ne répond pas. Vérifie ta connexion, puis recharge la page.");
  }

  if (loaded){
    await checkDraftAndStart(loaded);
  } else {
    hideLoadingOverlay();
    if (fetchError){
      $('#emptyStateErrorText').textContent = fetchError.message;
      $('#emptyStateError').style.display = 'block';
    }
    $('#emptyState').style.display = 'block';
  }
}

async function checkDraftAndStart(freshContent){
  // Référence pour les pastilles "X modifications" des sections : la
  // version normalisée de ce qui est réellement sur le serveur (ou du
  // fichier choisi manuellement), indépendamment du brouillon repris
  // ou non juste en dessous.
  try {
    PRISTINE_CONTENT = normalizeContent(JSON.parse(JSON.stringify(freshContent)));
  } catch(e){
    PRISTINE_CONTENT = null;
  }

  let draft = null;
  try {
    const raw = localStorage.getItem(DRAFT_KEY);
    if (raw) draft = JSON.parse(raw);
  } catch(e){
    // Brouillon illisible (corrompu) : on l'efface plutôt que de le
    // laisser bloqué là indéfiniment, et on continue sans lui.
    try { localStorage.removeItem(DRAFT_KEY); } catch(e2){}
  }

  /* Comparaison contre la version NORMALISÉE de freshContent (déjà
   * calculée juste au-dessus dans PRISTINE_CONTENT), pas contre
   * freshContent brut : le brouillon local, lui, est toujours issu
   * d'un CONTENT déjà passé par normalizeContent() (ordre des clés
   * fixe, valeurs par défaut ajoutées). Comparer un objet normalisé à
   * un objet brut faisait quasiment toujours ressortir une différence
   * textuelle même quand le contenu réel était rigoureusement
   * identique, et donc affichait « Brouillon retrouvé » à tort. */
  const referenceForCompare = PRISTINE_CONTENT || freshContent;

  if (draft && JSON.stringify(draft) !== JSON.stringify(referenceForCompare)){
    const keep = await customConfirm({
      title: 'Brouillon retrouvé',
      message: 'Un brouillon non téléchargé a été retrouvé (modifications précédentes non publiées).\n\nVeux-tu le reprendre, ou repartir du fichier content.json actuel ?',
      okText: 'Reprendre le brouillon',
      cancelText: 'Fichier actuel',
      icon: '📝'
    });
    if (!keep){
      // Brouillon explicitement refusé : on l'efface pour de bon, sinon
      // il ressurgirait à l'identique à chaque prochain chargement alors
      // qu'on vient justement de dire de ne pas le reprendre.
      try { localStorage.removeItem(DRAFT_KEY); } catch(e){}
    }
    // Si le choix retenu s'avère finalement inexploitable (brouillon
    // corrompu par exemple), startWith se replie automatiquement sur
    // l'autre source plutôt que de bloquer le chargement.
    startWith(keep ? draft : freshContent, keep ? freshContent : draft);
  } else {
    startWith(freshContent);
  }
}

let ORIGINAL_THEME = null;

/* Affiche l'écran « fichier inutilisable » (réutilise emptyState) avec
   un message clair, et propose d'en choisir un autre manuellement. */
function showLoadError(err){
  hideLoadingOverlay();
  $('#appMain').style.display = 'none';
  $('#actionbar').style.display = 'none';
  $('#emptyStateErrorText').textContent = (err && err.message) ? err.message : 'Fichier illisible ou dans un format inattendu.';
  $('#emptyStateError').style.display = 'block';
  $('#emptyState').style.display = 'block';
}

/* Démarre l'appli avec `data`. Si `data` s'avère inexploitable et
   qu'un `fallback` est fourni (ex. l'autre source disponible), on se
   replie dessus automatiquement au lieu de bloquer le chargement. Si
   le rendu lui-même échoue de façon inattendue après une donnée valide,
   on revient à l'état précédent plutôt que de laisser une page cassée. */
function startWith(data, fallback){
  let normalized;
  try {
    normalized = normalizeContent(data);
  } catch(err){
    if (fallback){
      showToast('Cette source était invalide (' + err.message + ') — reprise de l\u2019autre source disponible.', true);
      startWith(fallback);
      return;
    }
    showLoadError(err);
    return;
  }

  const previousContent = CONTENT;
  const previousTheme = ORIGINAL_THEME;
  try {
    CONTENT = normalized;
    ORIGINAL_THEME = JSON.parse(JSON.stringify(CONTENT.theme));
    hideLoadingOverlay();
    $('#emptyState').style.display = 'none';
    $('#appMain').style.display = 'block';
    $('#appMain').classList.add('is-ready');
    $('#actionbar').style.display = 'block';
    $('#previewBtn').style.display = 'inline-flex';
    renderAll();
    applyTheme(CONTENT.theme);
    setDirty(false);
    seedHistory('État initial');
    initActionbarSpacing();
    reportRepairsIfAny();
    scheduleSectionBadgesUpdate(true);
  } catch(err){
    console.error('Erreur au rendu du contenu :', err);
    if (previousContent){
      // On avait déjà quelque chose de fonctionnel affiché : on y revient
      // plutôt que de laisser une page à moitié rendue.
      CONTENT = previousContent;
      ORIGINAL_THEME = previousTheme;
      try { renderAll(); applyTheme(CONTENT.theme); } catch(e2){ /* déjà en échec, on laisse la bannière globale gérer */ }
      showToast('Impossible d\u2019afficher ce fichier (erreur interne) — tes modifications précédentes sont conservées.', true);
    } else if (fallback){
      startWith(fallback);
    } else {
      showLoadError(new Error('Le fichier semble valide mais son affichage a échoué de façon inattendue.'));
    }
  }
}

/* =====================================================================
   ESPACE RÉSERVÉ POUR LA BARRE D'ACTION FIXE
   La barre du bas peut occuper 1, 2 ou 3 lignes selon la largeur d'écran
   et le nombre de boutons (Annuler/Rétablir, Charger, Copier, Télécharger).
   On mesure sa vraie hauteur et on ajuste le padding du body en
   conséquence, pour ne jamais recouvrir ni couper le contenu — plutôt
   que de figer une valeur qui casse dès qu'un bouton est ajouté.
   ===================================================================== */
let actionbarSpacingInited = false;
function syncActionbarSpacing(){
  const bar = $('#actionbar');
  if (!bar || bar.style.display === 'none') return;
  const h = Math.ceil(bar.getBoundingClientRect().height);
  if (h > 0) document.body.style.paddingBottom = (h + 24) + 'px';
}
function initActionbarSpacing(){
  syncActionbarSpacing();
  if (actionbarSpacingInited) return;
  actionbarSpacingInited = true;
  const bar = $('#actionbar');
  if (window.ResizeObserver){
    new ResizeObserver(syncActionbarSpacing).observe(bar);
  }
  window.addEventListener('resize', syncActionbarSpacing);
  window.addEventListener('orientationchange', () => setTimeout(syncActionbarSpacing, 250));
  if (window.visualViewport){
    window.visualViewport.addEventListener('resize', syncActionbarSpacing);
  }
}

/* =====================================================================
   NORMALISATION ROBUSTE DU CONTENU CHARGÉ
   Un fichier content.json peut arriver corrompu, modifié à la main,
   incomplet ou d'une ancienne version du site. L'objectif : ne JAMAIS
   planter à cause de sa forme — au pire, on répare silencieusement en
   listant les réparations (LAST_REPAIRS), et on ne rejette le fichier
   en bloc que s'il n'est vraiment pas exploitable (pas un objet).
   ===================================================================== */
let LAST_REPAIRS = [];

function isPlainObject(v){ return !!v && typeof v === 'object' && !Array.isArray(v); }

/* Convertit une valeur en texte sûr pour l'affichage dans un champ.
   Un objet/tableau égaré à la place d'un texte est ignoré (remis à
   vide) plutôt que sérialisé en "[object Object]" dans le formulaire. */
function asString(v, fallback=''){
  if (typeof v === 'string') return v;
  if (v === null || v === undefined) return fallback;
  if (typeof v === 'number' || typeof v === 'boolean') return String(v);
  return fallback;
}

function normalizeObjectFields(obj, fields, label){
  const out = {};
  fields.forEach(key => {
    const before = obj[key];
    const val = asString(before);
    if (before !== undefined && before !== null && val === '' && String(before) !== ''){
      LAST_REPAIRS.push(`${label} : le champ « ${key} » n\u2019était pas un texte valide, remis à vide`);
    }
    out[key] = val;
  });
  return out;
}

function normalizeArrayOfObjects(arr, fields, label){
  if (!Array.isArray(arr)){
    if (arr !== undefined) LAST_REPAIRS.push(`« ${label} » n\u2019était pas une liste valide, remise à zéro`);
    return [];
  }
  const out = [];
  arr.forEach((item, i) => {
    if (!isPlainObject(item)){
      LAST_REPAIRS.push(`« ${label} » : élément #${i+1} invalide ignoré`);
      return;
    }
    out.push(normalizeObjectFields(item, fields, `${label} #${i+1}`));
  });
  return out;
}

const SERVICE_FIELDS_SHAPE = ['categorie','titre','image','description','prix','unite','cadrage','cadrageDesktop'];
const GALERIE_FIELDS_SHAPE  = ['src','alt'];

/* Clés du panneau « Visibilité du site ». Une clé absente du fichier (ou
   valant autre chose que `false`) est considérée visible par défaut — donc
   un content.json créé avant cette fonctionnalité s'ouvre avec tout affiché. */
const VISIBILITY_KEYS = ['presentation','pourquoi','tags','galerie','map','facebook','instagram'];

/* Prestations : mêmes champs texte que d'habitude + un booléen "visible"
   (masquage individuel d'un cours) géré à part, car normalizeArrayOfObjects
   n'assainit que des champs texte. */
function normalizeServices(arr){
  if (!Array.isArray(arr)){
    if (arr !== undefined) LAST_REPAIRS.push('« Prestations » n\u2019était pas une liste valide, remise à zéro');
    return [];
  }
  const out = [];
  arr.forEach((item, i) => {
    if (!isPlainObject(item)){
      LAST_REPAIRS.push('« Prestations » : élément #' + (i+1) + ' invalide ignoré');
      return;
    }
    const obj = normalizeObjectFields(item, SERVICE_FIELDS_SHAPE, 'Prestations #' + (i+1));
    obj.visible = item.visible !== false;
    out.push(obj);
  });
  return out;
}

function normalizeContent(data){
  LAST_REPAIRS = [];
  if (!isPlainObject(data)){
    throw new Error('Le fichier ne contient pas un objet JSON valide (attendu : { "contact": {...}, "services": [...], ... }).');
  }

  const out = {};

  // Étiquettes : liste de textes
  if (Array.isArray(data.tags)){
    const cleaned = data.tags.map(t => asString(t)).filter(t => t.trim() !== '');
    if (cleaned.length !== data.tags.length) LAST_REPAIRS.push('« tags » : éléments non textuels ou vides ignorés');
    out.tags = cleaned;
  } else {
    if (data.tags !== undefined) LAST_REPAIRS.push('« tags » n\u2019était pas une liste valide, remise à zéro');
    out.tags = [];
  }

  out.services = normalizeServices(data.services);
  out.galerie  = normalizeArrayOfObjects(data.galerie, GALERIE_FIELDS_SHAPE, 'Galerie');

  // Visibilité : objet de booléens, une clé manquante ou invalide retombe sur "visible".
  if (isPlainObject(data.visibility)){
    const vis = {};
    VISIBILITY_KEYS.forEach(k => { vis[k] = data.visibility[k] !== false; });
    out.visibility = vis;
  } else {
    if (data.visibility !== undefined) LAST_REPAIRS.push('« visibility » n\u2019était pas un objet valide, tout remis visible');
    const vis = {};
    VISIBILITY_KEYS.forEach(k => { vis[k] = true; });
    out.visibility = vis;
  }

  // Menu de navigation : id technique conservé tel quel, label assaini
  if (Array.isArray(data.nav)){
    const validItems = data.nav.filter(isPlainObject);
    if (validItems.length !== data.nav.length) LAST_REPAIRS.push('« nav » : éléments invalides ignorés');
    out.nav = validItems.map(item => ({ id: asString(item.id), label: asString(item.label) }));
  } else {
    if (data.nav !== undefined) LAST_REPAIRS.push('« nav » n\u2019était pas une liste valide, remise à zéro');
    out.nav = [];
  }

  // Objets simples : la validation fine des champs texte se fait à
  // l'affichage (bindSimpleFields utilise asString), on garantit ici
  // seulement que ce sont bien des objets et non des primitives/tableaux.
  ['contact','hero','presentation','pourquoi','footer','seo','images','galerieTexte'].forEach(key => {
    const val = data[key];
    if (isPlainObject(val)){
      out[key] = val;
    } else {
      if (val !== undefined) LAST_REPAIRS.push(`« ${key} » n\u2019était pas un objet valide, remis à zéro`);
      out[key] = {};
    }
  });

  // Thème : objet de couleurs — chaque valeur assainie en texte
  if (isPlainObject(data.theme)){
    const theme = {};
    Object.keys(data.theme).forEach(k => { theme[k] = asString(data.theme[k]); });
    out.theme = theme;
  } else {
    if (data.theme !== undefined) LAST_REPAIRS.push('« theme » n\u2019était pas un objet valide, remis aux couleurs par défaut');
    out.theme = {};
  }

  return out;
}

/* Affiche un résumé transparent si le chargement a nécessité des
   réparations automatiques (fichier partiellement corrompu). */
function reportRepairsIfAny(){
  if (!LAST_REPAIRS.length) return;
  console.warn('Réparations automatiques appliquées au chargement :', LAST_REPAIRS);
  showToast(
    LAST_REPAIRS.length === 1
      ? '1 élément du fichier était invalide et a été corrigé automatiquement — vérifie le contenu avant de publier.'
      : LAST_REPAIRS.length + ' éléments du fichier étaient invalides et ont été corrigés automatiquement — vérifie le contenu avant de publier.',
    true
  );
}

/* Import manuel (premier chargement, sans serveur) */
$('#manualFileInput').addEventListener('change', async (e) => {
  const file = e.target.files[0];
  if (!file) return;
  $('#emptyStateError').style.display = 'none';
  try {
    const text = await file.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch(parseErr){
      throw new Error('Ce fichier n\u2019est pas un JSON valide (' + parseErr.message + '). Vérifie qu\u2019il n\u2019a pas été coupé ou modifié accidentellement.');
    }
    await checkDraftAndStart(data);
  } catch(err){
    showLoadError(err);
  }
  e.target.value = '';
});

/* Avertissement avant d'ouvrir le sélecteur de fichier : charger un
   JSON à la main est une fonction technique, réservée au dépannage —
   on prévient et on laisse le choix d'annuler avant même d'ouvrir le
   sélecteur de fichier du navigateur. */
$('#reloadFileBtn').addEventListener('click', async () => {
  const proceed = await customConfirm({
    icon: '⚠️',
    title: 'Charger un fichier JSON',
    message: 'C\u2019est une fonction technique, réservée au dépannage : elle remplace tout le contenu affiché par un fichier content.json externe. Pour modifier le site (textes, prix, photos…), il est plus simple et plus sûr d\u2019utiliser directement les champs ci-dessus, puis « 🚀 Publier en ligne ». Continue seulement si tu sais ce que tu fais.',
    okText: 'Choisir un fichier quand même',
    cancelText: 'Annuler',
    danger: true
  });
  if (!proceed) return;
  $('#reloadFileInput').click();
});

/* Recharger un fichier depuis la barre d'action (remplace le contenu en cours) */
$('#reloadFileInput').addEventListener('change', async (e) => {
  const file = e.target.files[0];
  if (!file) return;
  if (dirty){
    const proceed = await customConfirm({
      title: 'Remplacer les modifications en cours ?',
      message: 'Charger ce fichier remplacera tes modifications en cours (non téléchargées).',
      okText: 'Charger le fichier',
      cancelText: 'Annuler',
      danger: true,
      icon: '📂'
    });
    if (!proceed){
      e.target.value = '';
      return;
    }
  }
  const previousContent = CONTENT;
  const previousTheme = ORIGINAL_THEME;
  try {
    const text = await file.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch(parseErr){
      throw new Error('Ce fichier n\u2019est pas un JSON valide (' + parseErr.message + ').');
    }
    const normalized = normalizeContent(data); // peut lever une erreur claire
    try {
      CONTENT = normalized;
      ORIGINAL_THEME = JSON.parse(JSON.stringify(CONTENT.theme));
      renderAll();
      applyTheme(CONTENT.theme);
    } catch(renderErr){
      // Le fichier passait la normalisation mais casse l'affichage :
      // on revient à ce qui fonctionnait, rien n'est perdu.
      CONTENT = previousContent;
      ORIGINAL_THEME = previousTheme;
      renderAll();
      applyTheme(CONTENT.theme);
      throw new Error('Ce fichier a provoqué une erreur d\u2019affichage — tes modifications précédentes ont été conservées.');
    }
    setDirty(false);
    seedHistory('Fichier chargé');
    PRISTINE_CONTENT = JSON.parse(JSON.stringify(normalized));
    scheduleSectionBadgesUpdate(true);
    reportRepairsIfAny();
    if (!LAST_REPAIRS.length) showToast('Fichier chargé.');
  } catch(err){
    showToast('Fichier non chargé : ' + err.message, true);
  }
  e.target.value = '';
});

/* =====================================================================
   RENDU
   ===================================================================== */
/* =====================================================================
   CONFORT DE SAISIE — deux améliorations transversales appliquées à
   TOUS les champs de l'admin, statiques ou générés dynamiquement
   (cours, galerie, étiquettes, menu…), sans avoir à toucher chaque
   panneau un par un :
   1) les zones de texte s'agrandissent toutes seules au fil de la
      frappe (fini le petit cadre à tirer manuellement pour voir ce
      qu'on écrit) ;
   2) un bouton "✕" apparaît dans un champ texte dès qu'il contient
      quelque chose, pour l'effacer d'un geste — pratique surtout au
      doigt, sur mobile, où sélectionner puis Suppr est fastidieux.
   Rejouées après chaque renderAll() (voir en bas de cette fonction),
   donc toujours à jour même après un rendu dynamique qui recrée les
   champs (nouvelle carte de cours, nouvelle photo de galerie…).
   ===================================================================== */
function autoGrowTextarea(el){
  el.style.height = 'auto';
  el.style.height = el.scrollHeight + 'px';
}
function autoGrowAllTextareas(){
  $$('textarea').forEach(autoGrowTextarea);
}
document.addEventListener('input', (e) => {
  if (e.target && e.target.tagName === 'TEXTAREA') autoGrowTextarea(e.target);
});

function enhanceClearableInputs(){
  $$('input[type=text], input[type=email], input[type=tel], input[type=url]').forEach(input => {
    // Les champs qui ont déjà leurs propres boutons dédiés (nom de fichier
    // image avec 🖼️/📷, champ couleur avec sa pastille) gardent leur mise
    // en page telle quelle — un bouton "effacer" de plus n'y aiderait pas.
    if (input.closest('.filepath-row') || input.closest('.color-row')) return;
    if (input.dataset.clearEnhanced) return;
    input.dataset.clearEnhanced = '1';
    const wrap = document.createElement('span');
    wrap.className = 'input-clear-wrap';
    input.parentNode.insertBefore(wrap, input);
    wrap.appendChild(input);
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'input-clear-btn';
    btn.setAttribute('aria-label', 'Effacer ce champ');
    btn.tabIndex = -1; // pratique à la souris/au tactile, pas une étape de plus au clavier
    btn.textContent = '✕';
    wrap.appendChild(btn);
    const sync = () => wrap.classList.toggle('has-value', !!input.value);
    sync();
    input.addEventListener('input', sync);
    btn.addEventListener('click', () => {
      input.value = '';
      input.dispatchEvent(new Event('input', { bubbles: true }));
      sync();
      input.focus();
    });
  });
}

function renderAll(){
  renderVisibility();
  bindSimpleFields();
  renderTags();
  renderServices();
  renderMainImages();
  renderGalerie();
  renderNav();
  renderTheme();
  renderSeoShareImage();
  enhanceClearableInputs();
  autoGrowAllTextareas();
}

/* --------------------------- Visibilité du site --------------------------- */
const VISIBILITY_ITEMS = [
  { key:'presentation', label:'Section « Présentation »', desc:'Le bloc « Bonjour, je suis Morgane » (photo, texte et étiquettes).' },
  { key:'pourquoi',     label:'Section « Pourquoi »',      desc:'Le bloc « Pourquoi suivre des cours d\u2019éducation canine ? »' },
  { key:'tags',         label:'Étiquettes',                desc:'Les petits mots-clés sous la présentation (ex. « Éducation canine »).' },
  { key:'galerie',      label:'Galerie photo',             desc:'Ne s\u2019affiche que si activée ET s\u2019il y a au moins 1 photo ajoutée (avec son nom de fichier renseigné).' },
  { key:'map',          label:'Carte de localisation',     desc:'La carte centrée sur la ville renseignée dans « Coordonnées de contact ».' },
  { key:'facebook',     label:'Lien Facebook',             desc:'L\u2019icône Facebook dans la section contact.' },
  { key:'instagram',    label:'Lien Instagram',            desc:'L\u2019icône Instagram dans la section contact.' }
];
function renderVisibility(){
  const grid = $('#visibilityGrid');
  grid.innerHTML = '';
  VISIBILITY_ITEMS.forEach(item => {
    const row = document.createElement('div');
    row.className = 'visibility-row';
    row.innerHTML = `
      <div class="visibility-row-text">
        <strong>${escapeHtml(item.label)}</strong>
        <span class="visibility-off-pill">Masqué</span>
        <p class="desc">${escapeHtml(item.desc)}</p>
      </div>
      <label class="switch" title="Afficher / masquer sur le site">
        <input type="checkbox">
        <span class="switch-track"><span class="switch-thumb"></span></span>
      </label>
    `;
    const checkbox = row.querySelector('input');
    const visible = CONTENT.visibility[item.key] !== false;
    checkbox.checked = visible;
    row.classList.toggle('is-off', !visible);
    checkbox.onchange = () => {
      CONTENT.visibility[item.key] = checkbox.checked;
      row.classList.toggle('is-off', !checkbox.checked);
      commitChange((checkbox.checked ? 'Réaffichage' : 'Masquage') + ' : ' + item.label);
    };
    grid.appendChild(row);
  });
}

function getPath(obj, path){
  return path.split('.').reduce((o,k)=> (o==null? undefined : o[k]), obj);
}
function setPath(obj, path, value){
  const parts = path.split('.');
  const last = parts.pop();
  let o = obj;
  for (const p of parts){ o[p] = o[p] || {}; o = o[p]; }
  o[last] = value;
}

function fieldLabelFor(el){
  const field = el.closest('.field');
  const lbl = field && field.querySelector('label');
  return lbl ? lbl.textContent.trim() : null;
}

/* Règles de validation des champs simples (liés via data-bind). Un champ
   sans règle ici reste libre (facultatif, sans contrainte de format). */
const FIELD_RULES = {
  'contact.email':       { required:true, type:'email' },
  'contact.telephone':   { required:true, type:'phone' },
  'contact.ville':       { required:true },
  'contact.zone':        { required:true },
  'contact.facebook':    { type:'url' },
  'contact.instagram':   { type:'url' },
  'hero.titre':          { required:true },
  'hero.texte':          { required:true },
  'presentation.titre':  { required:true },
  'presentation.texte1': { required:true },
  'presentation.texte2': { required:false },
  'pourquoi.titre':      { required:true },
  'pourquoi.texte1':     { required:true },
  'pourquoi.texte2':     { required:false },
  'pourquoi.citation':   { required:false },
  'galerieTexte.titre':  { required:false },
  'footer.copyright':    { required:true },
  'seo.siteTitle':       { required:true },
  'seo.description':     { required:true },
  'seo.siteUrl':         { required:true, type:'url' }
};
function validateByRule(value, rule){
  const v = (value || '').trim();
  if (rule.required && !v) return 'Ce champ est obligatoire.';
  if (!v) return null;
  if (rule.type === 'email' && !EMAIL_RE.test(v)) return 'Adresse e-mail invalide (ex. contact@exemple.fr).';
  if (rule.type === 'phone' && !isValidPhone(v)) return 'Numéro de téléphone invalide — attendu : 10 chiffres commençant par 0 (ex. 06 12 34 56 78) ou le même numéro au format international (ex. +33 6 12 34 56 78).';
  if (rule.type === 'url' && !URL_RE.test(v)) return 'Adresse invalide : elle doit commencer par http:// ou https:// et contenir un domaine (ex. https://exemple.fr).';
  return null;
}

function bindSimpleFields(){
  const validators = [];
  $$('[data-bind]').forEach(el => {
    const path = el.getAttribute('data-bind');
    const val = getPath(CONTENT, path);
    el.value = asString(val);
    const label = 'Modification : ' + (fieldLabelFor(el) || path);
    el.oninput = () => {
      setPath(CONTENT, path, el.value);
      commitChange(label, { debounce: true });
    };
    el.addEventListener('blur', flushHistoryDebounce);
    const rule = FIELD_RULES[path];
    if (rule) attachFieldValidation(el, (v) => validateByRule(v, rule), validators);
  });
  setSectionValidators('simple', validators);
  // Aperçu SEO en direct : mis à jour à chaque frappe sur les 3 champs
  // texte (référence de fonction stable → addEventListener ne duplique
  // jamais l'écouteur d'un appel à l'autre, comme pour flushHistoryDebounce
  // juste au-dessus), et recalculé ici à chaque passage pour rester
  // synchronisé après un chargement, une restauration ou un annuler/rétablir.
  ['seoTitleInput', 'seoDescInput', 'seoUrlInput'].forEach(id => {
    const el = $('#' + id);
    if (el) el.addEventListener('input', updateSeoPreview);
  });
  updateSeoPreview();
  // Même principe pour les compteurs du héros (badge + titre), courts et
  // sensibles à la mise en page si trop longs.
  const heroBadgeInput = $('#heroBadgeInput'), heroTitreInput = $('#heroTitreInput');
  if (heroBadgeInput) heroBadgeInput.addEventListener('input', updateHeroCounters);
  if (heroTitreInput) heroTitreInput.addEventListener('input', updateHeroCounters);
  updateHeroCounters();
  // Suggestion (non bloquante) si les liens Facebook/Instagram ne
  // pointent pas vers le bon domaine — souvent un signe de lien copié
  // par erreur (page d'un autre réseau, lien de partage générique…).
  const fbInput = $('[data-bind="contact.facebook"]'), igInput = $('[data-bind="contact.instagram"]');
  if (fbInput) attachSoftHint(fbInput, (v) => socialDomainHint(v, ['facebook.com', 'fb.com', 'fb.me']));
  if (igInput) attachSoftHint(igInput, (v) => socialDomainHint(v, ['instagram.com']));
  // Numéro de téléphone : mise en forme automatique "06 12 34 56 78"
  // uniquement pour un numéro français classique à 10 chiffres commençant
  // par 0 — un format différent (international, poste…) n'est jamais
  // retouché, pour ne jamais abîmer une saisie volontairement différente.
  const phoneInput = $('[data-bind="contact.telephone"]');
  if (phoneInput) phoneInput.addEventListener('input', formatPhoneInputLive);
  // Aperçus "comme sur le site" (héros, présentation, pourquoi) — même
  // principe de câblage que l'aperçu SEO ci-dessus : références de
  // fonctions stables, donc jamais dupliquées d'un passage à l'autre.
  ['hero.badge', 'hero.titre', 'hero.texte'].forEach(path => {
    const el = document.querySelector('[data-bind="' + path + '"]');
    if (el) el.addEventListener('input', updateHeroPreview);
  });
  updateHeroPreview();
  ['presentation.titre', 'presentation.texte1', 'presentation.texte2'].forEach(path => {
    const el = document.querySelector('[data-bind="' + path + '"]');
    if (el) el.addEventListener('input', updatePresentationPreview);
  });
  updatePresentationPreview();
  ['pourquoi.eyebrow', 'pourquoi.titre', 'pourquoi.texte1', 'pourquoi.texte2', 'pourquoi.citation'].forEach(path => {
    const el = document.querySelector('[data-bind="' + path + '"]');
    if (el) el.addEventListener('input', updateWhyPreview);
  });
  updateWhyPreview();
  ['contact.ville', 'contact.zone', 'contact.email', 'contact.telephone', 'contact.facebook', 'contact.instagram'].forEach(path => {
    const el = document.querySelector('[data-bind="' + path + '"]');
    if (el) el.addEventListener('input', updateContactPreview);
  });
  updateContactPreview();
  ['footer.copyright', 'footer.tagline'].forEach(path => {
    const el = document.querySelector('[data-bind="' + path + '"]');
    if (el) el.addEventListener('input', updateFooterPreview);
  });
  updateFooterPreview();
}

/* N'échappe le HTML que pour préserver <em>...</em> (seule balise que le
   site lui-même autorise dans hero.titre — voir l'astuce affichée sous
   ce champ) : tout le reste est neutralisé, pour ne jamais laisser un
   bout de balisage inattendu casser cet aperçu. */
function escapeHtmlKeepEm(s){
  return (s || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/&lt;em&gt;/g, '<em>')
    .replace(/&lt;\/em&gt;/g, '</em>');
}
function updateHeroPreview(){
  const badgeEl = $('#hmpBadge'), titleEl = $('#hmpTitle'), textEl = $('#hmpText');
  if (!titleEl) return;
  const badgeVal = (CONTENT.hero && CONTENT.hero.badge) || '';
  badgeEl.textContent = badgeVal;
  badgeEl.style.display = badgeVal ? 'inline-block' : 'none';
  titleEl.innerHTML = escapeHtmlKeepEm((CONTENT.hero && CONTENT.hero.titre) || 'Titre principal…');
  textEl.textContent = (CONTENT.hero && CONTENT.hero.texte) || 'Texte d\u2019introduction…';
}
function updatePresentationPreview(){
  const titleEl = $('#pmpTitle'), t1El = $('#pmpText1'), t2El = $('#pmpText2');
  if (!titleEl) return;
  titleEl.textContent = (CONTENT.presentation && CONTENT.presentation.titre) || 'Titre…';
  t1El.textContent = (CONTENT.presentation && CONTENT.presentation.texte1) || 'Premier paragraphe…';
  const t2Val = CONTENT.presentation && CONTENT.presentation.texte2;
  t2El.textContent = t2Val || '';
  t2El.style.display = t2Val ? 'block' : 'none';
}
function updateWhyPreview(){
  const eyebrowEl = $('#wmpEyebrow'), titleEl = $('#wmpTitle'), t1El = $('#wmpText1'), t2El = $('#wmpText2'), quoteEl = $('#wmpQuote');
  if (!titleEl) return;
  const eyebrowVal = CONTENT.pourquoi && CONTENT.pourquoi.eyebrow;
  eyebrowEl.textContent = eyebrowVal || '';
  eyebrowEl.style.display = eyebrowVal ? 'inline-block' : 'none';
  titleEl.textContent = (CONTENT.pourquoi && CONTENT.pourquoi.titre) || 'Titre…';
  t1El.textContent = (CONTENT.pourquoi && CONTENT.pourquoi.texte1) || 'Premier paragraphe…';
  const t2Val = CONTENT.pourquoi && CONTENT.pourquoi.texte2;
  t2El.textContent = t2Val || '';
  t2El.style.display = t2Val ? 'block' : 'none';
  const quoteVal = CONTENT.pourquoi && CONTENT.pourquoi.citation;
  quoteEl.textContent = quoteVal || '';
  quoteEl.style.display = quoteVal ? 'block' : 'none';
}
function updateContactPreview(){
  const locEl = $('#cmpLocation'), emailEl = $('#cmpEmail'), phoneEl = $('#cmpPhone');
  const fbEl = $('#cmpFacebook'), igEl = $('#cmpInstagram'), socialWrap = $('#cmpSocial');
  if (!locEl) return;
  const ville = (CONTENT.contact && CONTENT.contact.ville) || '';
  const zone = (CONTENT.contact && CONTENT.contact.zone) || '';
  locEl.textContent = [ville, zone].filter(Boolean).join(' — ') || 'Ville — Zone d\u2019intervention';
  emailEl.textContent = (CONTENT.contact && CONTENT.contact.email) || 'email@exemple.fr';
  phoneEl.textContent = (CONTENT.contact && CONTENT.contact.telephone) || '06 12 34 56 78';
  const fbVal = CONTENT.contact && CONTENT.contact.facebook;
  const igVal = CONTENT.contact && CONTENT.contact.instagram;
  // Reprend la même règle de visibilité que le site : un lien rempli
  // mais désactivé dans « 👁 Visibilité du site » ne s'affiche pas non
  // plus ici, pour ne jamais montrer un aperçu trompeur.
  const fbShown = !!fbVal && (CONTENT.visibility.facebook !== false);
  const igShown = !!igVal && (CONTENT.visibility.instagram !== false);
  fbEl.style.display = fbShown ? 'inline-block' : 'none';
  igEl.style.display = igShown ? 'inline-block' : 'none';
  socialWrap.style.display = (fbShown || igShown) ? 'flex' : 'none';
}
function updateFooterPreview(){
  const copyEl = $('#fmpCopy'), taglineEl = $('#fmpTagline');
  if (!copyEl) return;
  copyEl.textContent = (CONTENT.footer && CONTENT.footer.copyright) || 'Copyright…';
  const taglineVal = CONTENT.footer && CONTENT.footer.tagline;
  taglineEl.textContent = taglineVal || '';
  taglineEl.style.display = taglineVal ? 'inline' : 'none';
}

/* --------------------------- Confort de saisie : compteurs, formatage, suggestions --------------------------- */
function updateFieldCounter(inputEl, counterEl, max, warnAt){
  if (!inputEl || !counterEl) return;
  const n = (inputEl.value || '').length;
  if (!n){ counterEl.textContent = ''; counterEl.className = 'field-counter'; return; }
  counterEl.textContent = n + ' / ' + max + ' caractères';
  counterEl.className = 'field-counter' + (n > warnAt ? ' is-warn' : '');
}
function updateHeroCounters(){
  updateFieldCounter($('#heroBadgeInput'), $('#heroBadgeCounter'), 40, 32);
  updateFieldCounter($('#heroTitreInput'), $('#heroTitreCounter'), 90, 75);
}

/* Suggestion douce (jamais bloquante pour la publication) affichée sous
   un champ — même emplacement/logique que setFieldError, mais avec sa
   propre classe visuelle ".field-hint" (ton "conseil", pas "erreur").
   Idempotent : peut être rappelée à chaque renderAll() sur un champ
   statique sans jamais dupliquer ni le conteneur ni les écouteurs. */
function attachSoftHint(el, computeHint){
  const anchor = el.closest('.input-clear-wrap') || el;
  let holder = anchor.nextElementSibling;
  if (!holder || !holder.classList || !holder.classList.contains('field-hint')){
    holder = document.createElement('div');
    holder.className = 'field-hint';
    anchor.insertAdjacentElement('afterend', holder);
  }
  function run(){
    const msg = computeHint(el.value);
    holder.textContent = msg || '';
    holder.classList.toggle('show', !!msg);
  }
  if (!el.dataset.softHintAttached){
    el.dataset.softHintAttached = '1';
    el.addEventListener('blur', run);
    el.addEventListener('input', run);
  }
  run();
}
function socialDomainHint(value, domains){
  const v = (value || '').trim();
  if (!v || !URL_RE.test(v)) return null; // une adresse invalide est déjà signalée par l'erreur bloquante existante
  const matches = domains.some(d => v.toLowerCase().includes(d));
  return matches ? null : 'Ce lien ne semble pas pointer vers ' + domains[0] + ' — vérifie que c\u2019est bien la bonne adresse.';
}

/* Reformate en direct "0612345678" → "06 12 34 56 78", sans jamais
   toucher à un format différent (voir le commentaire dans bindSimpleFields).
   Redéclenche un évènement 'input' après reformatage pour que la valeur
   enregistrée dans CONTENT (gérée par l'écouteur .oninput déjà en place)
   soit bien la version reformatée, pas l'ancienne. */
function formatPhoneInputLive(){
  const el = this;
  const before = el.value;
  const digits = before.replace(/\D/g, '');
  if (!/^0\d{9}$/.test(digits)) return;
  const formatted = digits.replace(/(\d{2})(?=\d)/g, '$1 ');
  if (formatted === before) return;
  const start = el.selectionStart || 0;
  const diff = formatted.length - before.length;
  el.value = formatted;
  const pos = Math.max(0, start + diff);
  el.setSelectionRange(pos, pos);
  el.dispatchEvent(new Event('input', { bubbles: true }));
}

/* Retire les espaces en début/fin de champ à la perte de focus — évite
   les espaces invisibles qui traînent (copier-coller notamment) et qui
   peuvent fausser une comparaison ou un affichage. Sur tous les champs
   texte/zone de texte de l'admin, à l'exception des recherches (une
   recherche en cours n'a pas à être retouchée pendant qu'on la tape).
   Écouteur en phase de capture : 'blur' ne remonte pas (bubble) tout
   seul, contrairement à la plupart des évènements. */
document.addEventListener('blur', (e) => {
  const el = e.target;
  if (!el || (el.tagName !== 'INPUT' && el.tagName !== 'TEXTAREA')) return;
  const skipTypes = ['search', 'password', 'checkbox', 'radio', 'color', 'file', 'range', 'date', 'hidden'];
  if (el.tagName === 'INPUT' && skipTypes.includes(el.type)) return;
  const trimmed = el.value.replace(/^\s+/, '').replace(/\s+$/, '');
  if (trimmed !== el.value){
    el.value = trimmed;
    el.dispatchEvent(new Event('input', { bubbles: true }));
  }
}, true);

/* --------------------------- SEO : image de partage + aperçus --------------------------- */
function renderSeoShareImage(){
  const container = $('#seoShareImageWrap');
  if (!container) return;
  container.innerHTML = '';
  const validators = [];
  const wrap = document.createElement('div');
  wrap.className = 'field';
  wrap.innerHTML = `
    <label>Image de partage</label>
    <div class="img-fallback is-og" data-fb style="display:none;"></div>
    <img class="service-img-preview is-og" data-prev alt="">
    <div data-img-field></div>
    <div class="desc">L'image affichée quand un lien du site est partagé sur Facebook, Instagram ou WhatsApp. Laisse ce champ vide pour utiliser automatiquement la photo d'accueil. Format conseillé : environ 1200 × 630 px, à l'horizontale — le fichier doit se trouver dans le dossier <code>images/</code> du site.</div>
  `;
  const img = wrap.querySelector('[data-prev]');
  const fb = wrap.querySelector('[data-fb]');
  buildImageFilenameField(wrap.querySelector('[data-img-field]'), {
    prefix: 'images/',
    getFull: () => (CONTENT.seo && CONTENT.seo.shareImage) || '',
    setFull: (v) => { CONTENT.seo = CONTENT.seo || {}; CONTENT.seo.shareImage = v; },
    onCommit: () => { commitChange('Modification de l\u2019image de partage (SEO)', { debounce:true }); updateSeoPreview(); },
    imgEl: img, fallbackEl: fb,
    required: false,
    bucket: validators
  });
  container.appendChild(wrap);
  setSectionValidators('seoImage', validators);
  updateSeoPreview();
}

/* Recalcule les compteurs de caractères et les deux maquettes d'aperçu
   (Google + réseaux sociaux) à partir de ce qui est actuellement dans
   les champs — donc toujours à jour, même avant publication. */
function seoCounterClass(n, low, high){
  if (!n) return '';
  const margin = Math.round((high - low) * 0.15);
  if (n < low || n > high) return 'is-bad';
  if (n < low + margin || n > high - margin) return 'is-warn';
  return 'is-good';
}
function updateSeoPreview(){
  const titleEl = $('#seoTitleInput'), descEl = $('#seoDescInput'), urlEl = $('#seoUrlInput');
  if (!titleEl || !descEl) return;
  const title = titleEl.value || '';
  const desc = descEl.value || '';
  const url = ((urlEl && urlEl.value) || '').trim();

  const titleCounter = $('#seoTitleCounter');
  if (titleCounter){
    const n = title.length;
    let hint = '';
    if (n > 60) hint = ' — risque d\u2019être coupé dans Google';
    else if (n > 0 && n < 30) hint = ' — un peu court, tu peux en dire plus';
    titleCounter.textContent = n + ' caractère' + (n === 1 ? '' : 's') + hint;
    titleCounter.className = 'seo-counter ' + seoCounterClass(n, 30, 60);
  }
  const descCounter = $('#seoDescCounter');
  if (descCounter){
    const n = desc.length;
    let hint = '';
    if (n > 160) hint = ' — sera coupée dans Google';
    else if (n > 0 && n < 70) hint = ' — un peu courte, tu peux en dire plus';
    descCounter.textContent = n + ' caractère' + (n === 1 ? '' : 's') + hint;
    descCounter.className = 'seo-counter ' + seoCounterClass(n, 70, 160);
  }

  let domain = '';
  try { domain = url ? new URL(url).hostname.replace(/^www\./, '') : ''; } catch(e){ domain = url.replace(/^https?:\/\//, '').replace(/\/$/, ''); }

  const gSite = $('#seoGSite'); if (gSite) gSite.textContent = domain || 'votresite.fr';
  const gTitle = $('#seoGTitle'); if (gTitle) gTitle.textContent = title || 'Titre du site';
  const gDesc = $('#seoGDesc'); if (gDesc) gDesc.textContent = desc.length > 160 ? (desc.slice(0, 157) + '…') : (desc || 'Description du site…');

  const sDomain = $('#seoSDomain'); if (sDomain) sDomain.textContent = (domain || 'votresite.fr').toUpperCase();
  const sTitle = $('#seoSTitle'); if (sTitle) sTitle.textContent = title || 'Titre du site';
  const sDesc = $('#seoSDesc'); if (sDesc) sDesc.textContent = desc || 'Description du site…';
  const sImage = $('#seoSImage');
  if (sImage){
    // Comme pour les autres aperçus d'image de l'admin : `shareImage`/`images.hero`
    // contiennent déjà le chemin complet (ex. "images/hero.jpg"), pas seulement
    // le nom de fichier — donc pas de préfixe "images/" à rajouter ici.
    const shareImg = (CONTENT.seo && CONTENT.seo.shareImage) || (CONTENT.images && CONTENT.images.hero) || '';
    if (!shareImg){
      sImage.style.backgroundImage = 'none';
      sImage.textContent = 'Aucune image disponible pour l\u2019instant — ajoute une photo d\u2019accueil ou une image de partage ci-dessus.';
    } else {
      // Vérifie que le fichier existe réellement avant de l'afficher en fond
      // (même logique que refreshPreview() pour les autres champs image) :
      // sur l'admin, le fichier peut avoir été renommé/déplacé sans que ce
      // champ-ci ait été retouché, d'où un message explicite plutôt qu'un
      // cadre vide silencieux si l'image ne charge pas.
      sImage.textContent = '';
      const tester = new Image();
      tester.onload = () => { sImage.style.backgroundImage = `url('${shareImg}')`; sImage.textContent = ''; };
      tester.onerror = () => {
        sImage.style.backgroundImage = 'none';
        sImage.textContent = 'Aperçu indisponible ici (' + shareImg + ') — sera visible sur le site en ligne si le fichier existe à cet endroit.';
      };
      tester.src = shareImg;
    }
  }
}

/* --------------------------- Étiquettes / tags --------------------------- */
/* Réordonnables de deux façons complémentaires : glisser-déposer (souris)
   et flèches ↑ / ↓ (identiques au reste de l'admin — cours, galerie,
   menu — pour rester utilisables au clavier/tactile). */
let TAG_DRAG_INDEX = null;

function moveTag(from, to){
  if (from === to || from < 0 || from >= CONTENT.tags.length) return;
  to = Math.max(0, Math.min(CONTENT.tags.length - 1, to));
  const [moved] = CONTENT.tags.splice(from, 1);
  CONTENT.tags.splice(to, 0, moved);
  renderTags();
  commitChange('Réorganisation des étiquettes');
}

function renderTags(){
  const list = $('#tagList');
  list.innerHTML = '';
  CONTENT.tags.forEach((tag, i) => {
    const chip = document.createElement('span');
    chip.className = 'tag-chip';
    chip.draggable = true;
    chip.innerHTML = `
      <span class="tag-drag-handle" aria-hidden="true">⠿</span>
      <span class="tag-text"></span>
      <button type="button" class="tag-move" data-act="up" title="Monter" aria-label="Monter l\u2019étiquette">↑</button>
      <button type="button" class="tag-move" data-act="down" title="Descendre" aria-label="Descendre l\u2019étiquette">↓</button>
      <button type="button" class="tag-del" title="Supprimer" aria-label="Supprimer">✕</button>
    `;
    chip.querySelector('.tag-text').textContent = tag;

    const upBtn = chip.querySelector('[data-act=up]');
    const downBtn = chip.querySelector('[data-act=down]');
    upBtn.disabled = (i === 0);
    downBtn.disabled = (i === CONTENT.tags.length - 1);
    upBtn.onclick = () => moveTag(i, i - 1);
    downBtn.onclick = () => moveTag(i, i + 1);

    chip.querySelector('.tag-del').onclick = () => {
      CONTENT.tags.splice(i,1);
      renderTags();
      commitChange('Suppression de l\u2019étiquette « ' + tag + ' »');
    };

    chip.addEventListener('dragstart', (e) => {
      TAG_DRAG_INDEX = i;
      chip.classList.add('is-dragging');
      e.dataTransfer.effectAllowed = 'move';
      try { e.dataTransfer.setData('text/plain', String(i)); } catch(err){}
    });
    chip.addEventListener('dragend', () => {
      chip.classList.remove('is-dragging');
      $$('.tag-chip', list).forEach(c => c.classList.remove('drag-over-before','drag-over-after'));
      TAG_DRAG_INDEX = null;
    });
    chip.addEventListener('dragover', (e) => {
      if (TAG_DRAG_INDEX === null) return;
      e.preventDefault();
      const rect = chip.getBoundingClientRect();
      const before = (e.clientX - rect.left) < rect.width / 2;
      chip.classList.toggle('drag-over-before', before);
      chip.classList.toggle('drag-over-after', !before);
    });
    chip.addEventListener('dragleave', () => {
      chip.classList.remove('drag-over-before','drag-over-after');
    });
    chip.addEventListener('drop', (e) => {
      e.preventDefault();
      const from = TAG_DRAG_INDEX;
      const before = chip.classList.contains('drag-over-before');
      chip.classList.remove('drag-over-before','drag-over-after');
      if (from === null || from === i) return;
      let target = i;
      if (from < i) target -= 1;
      if (!before) target += 1;
      moveTag(from, target);
    });

    list.appendChild(chip);
  });
}
function addTag(){
  const input = $('#newTagInput');
  const errEl = $('#tagError');
  const val = input.value.trim();
  function fail(msg){
    input.classList.add('invalid');
    errEl.textContent = msg;
    errEl.classList.add('show');
  }
  if (!val){ fail('Indique une étiquette avant de l\u2019ajouter.'); input.focus(); return; }
  const exists = CONTENT.tags.some(t => t.trim().toLowerCase() === val.toLowerCase());
  if (exists){ fail('Cette étiquette existe déjà.'); input.focus(); return; }
  input.classList.remove('invalid');
  errEl.textContent = '';
  errEl.classList.remove('show');
  CONTENT.tags.push(val);
  input.value = '';
  renderTags();
  commitChange('Ajout de l\u2019étiquette « ' + val + ' »');
}
$('#addTagBtn').addEventListener('click', addTag);
$('#newTagInput').addEventListener('keydown', (e) => { if (e.key === 'Enter'){ e.preventDefault(); addTag(); } });
$('#newTagInput').addEventListener('input', function(){
  this.classList.remove('invalid');
  const errEl = $('#tagError');
  errEl.textContent = '';
  errEl.classList.remove('show');
});

/* --------------------------- Cours & tarifs --------------------------- */
const SERVICE_REQUIRED = { categorie:true, titre:true, prix:false, unite:false, description:true };
const SERVICE_FIELD_LABELS = { categorie:'Catégorie', titre:'Titre', prix:'Prix', unite:'Unité', description:'Description', cadrage:'Cadrage (mobile)', cadrageDesktop:'Cadrage (grand écran)' };

/* Déplie/replie une fiche de prestation dans l'admin (indépendant des
   panneaux) — même logique d'animation que togglePanel, mais réutilisable
   avec un booléen explicite (open) et un mode sans animation (utilisé au
   premier rendu et pour révéler un champ en erreur). */
function setServiceCardOpen(card, open, animate){
  const body = card.querySelector('[data-role=body]');
  if (!body) return;
  const isOpen = card.classList.contains('open');
  if (open === isOpen) return;
  if (open){
    card.classList.add('open');
    body.style.display = 'block';
    if (!animate){ body.style.maxHeight = 'none'; body.style.opacity = '1'; return; }
    const target = body.scrollHeight;
    body.style.maxHeight = '0px';
    body.style.opacity = '0';
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        body.style.maxHeight = target + 'px';
        body.style.opacity = '1';
      });
    });
    const onEnd = (e) => {
      if (e.propertyName !== 'max-height') return;
      body.style.maxHeight = 'none';
      body.removeEventListener('transitionend', onEnd);
    };
    body.addEventListener('transitionend', onEnd);
  } else {
    if (!animate){
      card.classList.remove('open');
      body.style.display = 'none';
      body.style.maxHeight = '0px';
      body.style.opacity = '0';
      return;
    }
    const current = body.scrollHeight;
    body.style.maxHeight = current + 'px';
    body.style.opacity = '1';
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        body.style.maxHeight = '0px';
        body.style.opacity = '0';
      });
    });
    const onEnd = (e) => {
      if (e.propertyName !== 'max-height') return;
      card.classList.remove('open');
      body.style.display = 'none';
      body.removeEventListener('transitionend', onEnd);
    };
    body.addEventListener('transitionend', onEnd);
  }
}

function renderServices(){
  const list = $('#servicesList');
  list.innerHTML = '';
  const validators = [];
  CONTENT.services.forEach((svc, i) => {
    const card = document.createElement('div');
    card.className = 'service-card';
    card.innerHTML = `
      <div class="service-card-top" data-role="header">
        <div class="service-card-title">
          <span class="cat">Prestation ${i+1}</span>
          <span data-role="titre-text">${escapeHtml(svc.titre || '(sans titre)')}</span>
          <span class="service-hidden-badge">Masqué sur le site</span>
          <span class="service-card-subtitle" data-role="subtitle"></span>
        </div>
        <div class="service-actions">
          <label class="switch" title="Afficher / masquer ce cours sur le site">
            <input type="checkbox" data-act="visible">
            <span class="switch-track"><span class="switch-thumb"></span></span>
          </label>
          <button type="button" class="btn btn-icon btn-outline" data-act="up" title="Monter">↑</button>
          <button type="button" class="btn btn-icon btn-outline" data-act="down" title="Descendre">↓</button>
          <button type="button" class="btn btn-icon btn-outline" data-act="dup" title="Dupliquer">⧉</button>
          <button type="button" class="btn btn-icon btn-danger" data-act="del" title="Supprimer">🗑</button>
          <button type="button" class="btn btn-icon btn-outline" data-act="collapse" title="Déplier / replier ce cours">
            <svg class="service-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 9l6 6 6-6"/></svg>
          </button>
        </div>
      </div>
      <div class="service-card-body" data-role="body">
        <div class="img-fallback" data-img-fallback style="display:none;"></div>
        <img class="service-img-preview" data-img-preview alt="">
        <div class="grid-3">
          <div class="field"><label>Catégorie *</label><input type="text" data-f="categorie" spellcheck="true" lang="fr"></div>
          <div class="field"><label>Titre *</label><input type="text" data-f="titre" spellcheck="true" lang="fr"></div>
          <div class="field"><label>Nom du fichier image</label><div data-img-field></div><div class="desc">Le fichier doit se trouver dans le dossier <code>images/</code> du site.</div></div>
        </div>
        <div class="field">
          <label>Cadrage de la photo</label>
          <div class="desc">La photo n'est pas recadrée pareil partout sur le site : en bande large sur mobile, en colonne haute et étroite sur grand écran (fiche avec la photo à côté du texte). Règle les deux séparément si le sujet se retrouve coupé.</div>
          <div class="focal-pickers">
            <div class="focal-picker" data-focal="mobile"></div>
            <div class="focal-picker" data-focal="desktop"></div>
          </div>
        </div>
        <div class="grid-2">
          <div class="field"><label>Prix</label><input type="text" data-f="prix" placeholder="ex. 50 €" spellcheck="false"><div class="desc">Laisser vide pour n'afficher aucun bloc prix sur cette prestation.</div></div>
          <div class="field"><label>Unité</label><input type="text" data-f="unite" placeholder="ex. / heure" spellcheck="true" lang="fr"></div>
        </div>
        <div class="field"><label>Description *</label><textarea class="tall" data-f="description" spellcheck="true" lang="fr"></textarea><div class="desc">Les sauts de ligne, apostrophes et caractères spéciaux (é, «, —…) sont conservés tels quels.</div></div>
      </div>
    `;

    // repli/dépli — replié par défaut pour repérer les cours plus facilement
    // dans la liste ; le titre, la catégorie et le prix restent visibles
    // même replié.
    const toggleThisCard = () => setServiceCardOpen(card, !card.classList.contains('open'), true);
    card.querySelector('.service-card-title').onclick = toggleThisCard;
    card.querySelector('[data-act=collapse]').onclick = toggleThisCard;

    function updateSubtitle(){
      const bits = [svc.categorie, [svc.prix, svc.unite].filter(Boolean).join(' ')].filter(Boolean);
      card.querySelector('[data-role=subtitle]').textContent = bits.join(' · ');
    }
    updateSubtitle();

    // visibilité de ce cours (masquage individuel, sans suppression)
    const visToggle = card.querySelector('[data-act=visible]');
    visToggle.checked = svc.visible !== false;
    card.classList.toggle('is-hidden', !visToggle.checked);
    visToggle.onchange = () => {
      svc.visible = visToggle.checked;
      card.classList.toggle('is-hidden', !visToggle.checked);
      commitChange((visToggle.checked ? 'Réaffichage' : 'Masquage') + ' du cours « ' + (svc.titre || 'Prestation ' + (i+1)) + ' »');
    };

    // valeurs
    card.querySelector('[data-f=categorie]').value = svc.categorie || '';
    card.querySelector('[data-f=titre]').value = svc.titre || '';
    card.querySelector('[data-f=prix]').value = svc.prix || '';
    card.querySelector('[data-f=unite]').value = svc.unite || '';
    card.querySelector('[data-f=description]').value = svc.description || '';

    // champ image (dossier forcé)
    const imgEl = card.querySelector('[data-img-preview]');
    const fallbackEl = card.querySelector('[data-img-fallback]');
    // Le cadrage (point focal) est réglé pour UNE photo précise : si le
    // fichier image change, un ancien réglage resterait invisible mais
    // continuerait à s'appliquer à la nouvelle photo, avec un résultat
    // imprévisible (le point retenu peut tomber n'importe où sur le
    // nouveau sujet). On revient donc au centrage par défaut dès que
    // l'image change réellement — une seule fois, pas à chaque frappe
    // une fois déjà remis à vide.
    let lastImageForCadrage = svc.image || '';
    function resetCadrageIfImageChanged(){
      const curr = svc.image || '';
      if (curr === lastImageForCadrage) return;
      lastImageForCadrage = curr;
      if (svc.cadrage || svc.cadrageDesktop){
        svc.cadrage = '';
        svc.cadrageDesktop = '';
        refreshFocalPickers();
        commitChange('Réinitialisation du cadrage — nouvelle image (Prestation ' + (i+1) + ')', { debounce:true });
      }
    }
    buildImageFilenameField(card.querySelector('[data-img-field]'), {
      prefix: 'images/',
      getFull: () => svc.image || '',
      setFull: (v) => { svc.image = v; },
      onCommit: () => {
        commitChange('Modification : Image (Prestation ' + (i+1) + ')', { debounce:true });
        resetCadrageIfImageChanged();
        refreshFocalPickers();
      },
      imgEl, fallbackEl,
      required: false,
      bucket: validators
    });

    // cadrage (point focal) — mobile et grand écran séparément, car la
    // photo n'occupe pas le même format dans les deux cas (voir
    // buildFocalPointField ci-dessus)
    const focalMobile = buildFocalPointField(card.querySelector('[data-focal=mobile]'), {
      variant: 'mobile',
      label: '📱 Mobile',
      tag: 'carte + fiche',
      desc: 'Vignette et fiche empilée (écran étroit).',
      getImageUrl: () => svc.image || '',
      getValue: () => svc.cadrage || '',
      setValue: (v) => { svc.cadrage = v; },
      onCommit: (opts) => commitChange('Modification : ' + SERVICE_FIELD_LABELS.cadrage + ' (Prestation ' + (i+1) + ')', opts),
      syncLabel: 'vers 🖥️',
      onSyncTo: (v) => {
        svc.cadrageDesktop = v;
        refreshFocalPickers();
        commitChange('Cadrage copié : 📱 Mobile → 🖥️ Grand écran (Prestation ' + (i+1) + ')', { debounce:false });
      }
    });
    const focalDesktop = buildFocalPointField(card.querySelector('[data-focal=desktop]'), {
      variant: 'desktop',
      label: '🖥️ Grand écran',
      tag: 'fiche, ≥ 900px',
      desc: 'Fiche à deux colonnes, la photo occupe toute la colonne de gauche.',
      getImageUrl: () => svc.image || '',
      getValue: () => svc.cadrageDesktop || '',
      setValue: (v) => { svc.cadrageDesktop = v; },
      onCommit: (opts) => commitChange('Modification : ' + SERVICE_FIELD_LABELS.cadrageDesktop + ' (Prestation ' + (i+1) + ')', opts),
      syncLabel: 'vers 📱',
      onSyncTo: (v) => {
        svc.cadrage = v;
        refreshFocalPickers();
        commitChange('Cadrage copié : 🖥️ Grand écran → 📱 Mobile (Prestation ' + (i+1) + ')', { debounce:false });
      }
    });
    function refreshFocalPickers(){ focalMobile.refresh(); focalDesktop.refresh(); }

    // bindings des champs texte
    $$('[data-f]', card).forEach(el => {
      const key = el.getAttribute('data-f');
      el.oninput = () => {
        svc[key] = el.value;
        commitChange('Modification : ' + SERVICE_FIELD_LABELS[key] + ' (Prestation ' + (i+1) + ')', { debounce:true });
        if (key === 'titre'){ card.querySelector('[data-role=titre-text]').textContent = svc.titre || '(sans titre)'; }
        if (key === 'categorie' || key === 'prix' || key === 'unite'){ updateSubtitle(); }
      };
      el.addEventListener('blur', flushHistoryDebounce);
      if (SERVICE_REQUIRED[key]){
        attachFieldValidation(el, (v) => (v || '').trim() ? null : 'Ce champ est obligatoire.', validators);
      }
    });

    // actions
    card.querySelector('[data-act=up]').onclick = () => {
      if (i === 0) return;
      [CONTENT.services[i-1], CONTENT.services[i]] = [CONTENT.services[i], CONTENT.services[i-1]];
      renderServices(); commitChange('Réorganisation des prestations');
    };
    card.querySelector('[data-act=down]').onclick = () => {
      if (i === CONTENT.services.length-1) return;
      [CONTENT.services[i+1], CONTENT.services[i]] = [CONTENT.services[i], CONTENT.services[i+1]];
      renderServices(); commitChange('Réorganisation des prestations');
    };
    card.querySelector('[data-act=dup]').onclick = () => {
      const copy = JSON.parse(JSON.stringify(svc));
      copy.titre = (copy.titre || '') + ' (copie)';
      CONTENT.services.splice(i+1, 0, copy);
      renderServices(); commitChange('Duplication de « ' + (svc.titre || 'une prestation') + ' »');
    };
    card.querySelector('[data-act=del]').onclick = async () => {
      const ok = await customConfirm({
        title: 'Supprimer cette prestation ?',
        message: `« ${svc.titre || 'Cette prestation'} » sera définitivement supprimée.`,
        okText: 'Supprimer',
        cancelText: 'Annuler',
        danger: true
      });
      if (!ok) return;
      CONTENT.services.splice(i,1);
      renderServices(); commitChange('Suppression de « ' + (svc.titre || 'une prestation') + ' »');
    };
    card.querySelector('[data-act=up]').disabled = (i===0);
    card.querySelector('[data-act=down]').disabled = (i===CONTENT.services.length-1);

    list.appendChild(card);
  });
  setSectionValidators('services', validators);
}

$('#addServiceBtn').addEventListener('click', () => {
  CONTENT.services.push({ categorie:'', titre:'Nouvelle prestation', image:'', description:'', prix:'', unite:'', cadrage:'', cadrageDesktop:'', visible:true });
  renderServices();
  commitChange('Ajout d\u2019une prestation');
  const cards = $$('.service-card');
  const newCard = cards[cards.length-1];
  // Le nouveau cours reste déplié (contrairement aux autres, repliés par
  // défaut) : il est vide et destiné à être rempli tout de suite.
  setServiceCardOpen(newCard, true, false);
  newCard.scrollIntoView({ behavior:'smooth', block:'center' });
});

function escapeHtml(s){
  return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

/* --------------------------- Images principales --------------------------- */
const MAIN_IMAGE_FIELDS = [
  { key: 'hero', label: 'Photo d\u2019accueil (héros)', desc: 'Grande photo affichée tout en haut du site.' },
  { key: 'contact', label: 'Fond de la section contact', desc: 'Image de fond derrière le bloc de contact.' },
  { key: 'portrait', label: 'Portrait', desc: 'Photo de profil affichée dans la section « Présentation ».' }
];
function renderMainImages(){
  const body = $('#mainImagesBody');
  body.innerHTML = '';
  const grid = document.createElement('div');
  grid.className = 'main-images-grid';
  const validators = [];
  const MAIN_IMAGE_RATIO_CLASS = { hero: 'is-hero', contact: 'is-wide', portrait: 'is-portrait' };
  MAIN_IMAGE_FIELDS.forEach(f => {
    const ratioClass = MAIN_IMAGE_RATIO_CLASS[f.key] || '';
    const wrap = document.createElement('div');
    wrap.className = 'field';
    wrap.innerHTML = `
      <label>${f.label}</label>
      <div class="img-fallback ${ratioClass}" data-fb style="display:none;"></div>
      <img class="service-img-preview ${ratioClass}" data-prev alt="">
      <div data-img-field></div>
      <div class="desc">${f.desc} Le fichier doit se trouver dans le dossier <code>images/</code> du site.</div>
    `;
    const img = wrap.querySelector('[data-prev]');
    const fb = wrap.querySelector('[data-fb]');
    buildImageFilenameField(wrap.querySelector('[data-img-field]'), {
      prefix: 'images/',
      getFull: () => CONTENT.images[f.key] || '',
      setFull: (v) => { CONTENT.images[f.key] = v; },
      onCommit: () => commitChange('Modification de l\u2019image « ' + f.label + ' »', { debounce:true }),
      imgEl: img, fallbackEl: fb,
      required: false,
      bucket: validators
    });
    grid.appendChild(wrap);
  });
  body.appendChild(grid);
  setSectionValidators('mainImages', validators);
}

/* --------------------------- Galerie --------------------------- */
const GALLERY_LAYOUT_OPTIONS = [
  { key:'grid', label:'Grille', tag:'Le classique', desc:'Carrés bien rangés, taille identique — le style actuel.', preview:'gl-grid', tiles:8 },
  { key:'mosaic', label:'Mosaïque', tag:'Plus vivant', desc:'Une photo mise en avant, plus grande, au milieu des autres.', preview:'gl-mosaic', tiles:8 },
  { key:'carousel', label:'Carrousel', tag:'Beaucoup de photos', desc:'Les photos défilent horizontalement au doigt ou à la souris.', preview:'gl-carousel', tiles:5 }
];
function renderGalleryLayoutPicker(){
  const wrap = $('#galleryLayoutPicker');
  if (!wrap) return;
  wrap.innerHTML = '';
  const current = (CONTENT.galerieTexte && CONTENT.galerieTexte.layout) || 'grid';
  GALLERY_LAYOUT_OPTIONS.forEach(opt => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'gallery-layout-option' + (opt.key === current ? ' is-current' : '');
    btn.innerHTML = `
      <div class="gallery-layout-preview ${opt.preview}">${'<span></span>'.repeat(opt.tiles)}</div>
      <div class="gallery-layout-option-label">${opt.label}</div>
      <div class="gallery-layout-option-tag">${opt.tag}</div>
      <div class="gallery-layout-option-desc">${opt.desc}</div>
    `;
    btn.addEventListener('click', () => {
      if ((CONTENT.galerieTexte && CONTENT.galerieTexte.layout) === opt.key) return;
      CONTENT.galerieTexte = CONTENT.galerieTexte || {};
      CONTENT.galerieTexte.layout = opt.key;
      renderGalleryLayoutPicker();
      commitChange('Type d\u2019affichage de la galerie : ' + opt.label);
    });
    wrap.appendChild(btn);
  });
  renderGalleryLivePreview();
}
/* Aperçu simplifié : jusqu'à 8 vraies photos de la galerie (celles qui
   ont un nom de fichier renseigné), avec la mise en page actuellement
   choisie. Un "+N" discret indique qu'il y en a davantage sur le site
   réel — pas la peine de tout charger ici pour un simple aperçu. */
function renderGalleryLivePreview(){
  const wrap = $('#galleryLivePreview');
  if (!wrap) return;
  wrap.className = 'gallery-live-preview';
  const layout = (CONTENT.galerieTexte && CONTENT.galerieTexte.layout) || 'grid';
  if (layout === 'mosaic') wrap.classList.add('is-mosaic');
  if (layout === 'carousel') wrap.classList.add('is-carousel');
  const photos = (CONTENT.galerie || []).filter(p => p && p.src);
  wrap.innerHTML = '';
  if (!photos.length){
    wrap.innerHTML = '<div class="gallery-live-preview-empty">Ajoute des photos ci-dessous pour voir un aperçu du rendu.</div>';
    return;
  }
  const MAX_PREVIEW = 8;
  photos.slice(0, MAX_PREVIEW).forEach(p => {
    const item = document.createElement('div');
    item.className = 'glp-item';
    item.innerHTML = `<img src="${escapeHtml(p.src)}" alt="" loading="lazy" onerror="this.classList.add('img-error')">`;
    wrap.appendChild(item);
  });
  if (photos.length > MAX_PREVIEW){
    const more = document.createElement('div');
    more.className = 'gallery-live-preview-more';
    more.textContent = '+' + (photos.length - MAX_PREVIEW);
    wrap.appendChild(more);
  }
}
function renderGalerie(){
  renderGalleryLayoutPicker();
  const list = $('#galerieList');
  list.innerHTML = '';
  const validators = [];
  const validCount = CONTENT.galerie.filter(p => p && p.src).length;
  const count = CONTENT.galerie.length;
  const statusEl = $('#galerieStatus');
  statusEl.innerHTML = validCount >= 1
    ? `<strong style="color:var(--ok);">${count} photo(s)</strong> — la section Galerie est visible sur le site.`
    : `<strong style="color:var(--danger);">${count} photo(s)</strong> — ajoute au moins une photo (avec son nom de fichier renseigné) pour que la section apparaisse sur le site.`;

  CONTENT.galerie.forEach((photo, i) => {
    const card = document.createElement('div');
    card.className = 'service-card';
    card.innerHTML = `
      <div class="service-card-top">
        <div class="service-card-title"><span class="cat">Photo ${i+1}</span></div>
        <div class="service-actions">
          <button type="button" class="btn btn-icon btn-outline" data-act="first" title="Mettre en premier">⇤</button>
          <button type="button" class="btn btn-icon btn-outline" data-act="up" title="Monter">↑</button>
          <button type="button" class="btn btn-icon btn-outline" data-act="down" title="Descendre">↓</button>
          <button type="button" class="btn btn-icon btn-outline" data-act="last" title="Mettre en dernier">⇥</button>
          <button type="button" class="btn btn-icon btn-danger" data-act="del" title="Supprimer">🗑</button>
        </div>
      </div>
      <div class="img-fallback is-square" data-fb style="display:none;"></div>
      <img class="service-img-preview is-square" data-prev alt="">
      <div class="field"><label>Nom du fichier image *</label><div data-img-field></div><div class="desc">Le fichier doit se trouver dans le dossier <code>images/galerie/</code> du site.</div></div>
      <div class="field"><label>Texte alternatif (description courte de la photo) *</label><input type="text" data-f="alt" placeholder="ex. Chien en balade en forêt" spellcheck="true" lang="fr"></div>
    `;
    const img = card.querySelector('[data-prev]');
    const fb = card.querySelector('[data-fb]');
    buildImageFilenameField(card.querySelector('[data-img-field]'), {
      prefix: 'images/galerie/',
      getFull: () => photo.src || '',
      setFull: (v) => { photo.src = v; },
      onCommit: () => { commitChange('Modification : Image (Photo ' + (i+1) + ')', { debounce:true }); renderGalleryLivePreview(); },
      imgEl: img, fallbackEl: fb,
      required: true,
      bucket: validators
    });

    card.querySelector('[data-f=alt]').value = photo.alt || '';
    const altInput = card.querySelector('[data-f=alt]');
    altInput.oninput = () => { photo.alt = altInput.value; commitChange('Modification : Texte alternatif (Photo ' + (i+1) + ')', { debounce:true }); };
    altInput.addEventListener('blur', flushHistoryDebounce);
    attachFieldValidation(altInput, (v) => (v || '').trim() ? null : 'Indique un texte alternatif décrivant la photo (accessibilité et référencement).', validators);

    card.querySelector('[data-act=first]').onclick = () => { if (i===0) return; const [p] = CONTENT.galerie.splice(i,1); CONTENT.galerie.unshift(p); renderGalerie(); commitChange('Photo mise en premier dans la galerie'); };
    card.querySelector('[data-act=up]').onclick = () => { if (i===0) return; [CONTENT.galerie[i-1],CONTENT.galerie[i]]=[CONTENT.galerie[i],CONTENT.galerie[i-1]]; renderGalerie(); commitChange('Réorganisation de la galerie'); };
    card.querySelector('[data-act=down]').onclick = () => { if (i===CONTENT.galerie.length-1) return; [CONTENT.galerie[i+1],CONTENT.galerie[i]]=[CONTENT.galerie[i],CONTENT.galerie[i+1]]; renderGalerie(); commitChange('Réorganisation de la galerie'); };
    card.querySelector('[data-act=last]').onclick = () => { if (i===CONTENT.galerie.length-1) return; const [p] = CONTENT.galerie.splice(i,1); CONTENT.galerie.push(p); renderGalerie(); commitChange('Photo mise en dernier dans la galerie'); };
    card.querySelector('[data-act=del]').onclick = async () => {
      const ok = await customConfirm({
        title: 'Supprimer cette photo ?',
        message: 'Elle sera retirée définitivement de la galerie.',
        okText: 'Supprimer',
        cancelText: 'Annuler',
        danger: true
      });
      if (!ok) return;
      CONTENT.galerie.splice(i,1); renderGalerie(); commitChange('Suppression d\u2019une photo de la galerie');
    };
    card.querySelector('[data-act=first]').disabled = (i===0);
    card.querySelector('[data-act=up]').disabled = (i===0);
    card.querySelector('[data-act=down]').disabled = (i===CONTENT.galerie.length-1);
    card.querySelector('[data-act=last]').disabled = (i===CONTENT.galerie.length-1);
    list.appendChild(card);
  });
  setSectionValidators('galerie', validators);
  const clearBtn = $('#clearGalerieBtn');
  if (clearBtn) clearBtn.style.display = CONTENT.galerie.length ? 'inline-flex' : 'none';
}
$('#addGalerieBtn').addEventListener('click', () => {
  CONTENT.galerie.push({ src:'', alt:'' });
  renderGalerie();
  commitChange('Ajout d\u2019une photo à la galerie');
  const cards = $$('#galerieList .service-card');
  if (cards.length) cards[cards.length-1].scrollIntoView({ behavior:'smooth', block:'center' });
});
$('#clearGalerieBtn').addEventListener('click', async () => {
  const n = CONTENT.galerie.length;
  if (!n) return;
  const ok = await customConfirm({
    icon: '🗑',
    title: 'Vider entièrement la galerie ?',
    message: `Les ${n} photo${n>1?'s':''} de la galerie seront retirées d\u2019un coup. Les fichiers restent sur le serveur (utilise « 🧹 Nettoyer les photos inutilisées » pour les supprimer aussi) — seule la liste de la galerie est vidée ici.`,
    okText: 'Vider la galerie',
    cancelText: 'Annuler',
    danger: true
  });
  if (!ok) return;
  CONTENT.galerie = [];
  renderGalerie();
  commitChange('Galerie entièrement vidée');
  showToast('La galerie a été vidée — pense à publier pour appliquer ce changement.');
});

/* --------------------------- Menu de navigation --------------------------- */
function renderNav(){
  const list = $('#navList');
  list.innerHTML = '';
  const validators = [];
  CONTENT.nav.forEach((item, i) => {
    const row = document.createElement('div');
    row.className = 'field';
    row.style.display = 'flex';
    row.style.alignItems = 'center';
    row.style.gap = '10px';
    row.innerHTML = `
      <div style="flex:1; min-width:0;">
        <label>Lien vers « ${escapeHtml(item.id || '')} »</label>
        <input type="text" data-inp spellcheck="true" lang="fr">
      </div>
      <button type="button" class="btn btn-icon btn-outline" data-act="up" title="Monter" style="margin-top:20px; flex:none;">↑</button>
      <button type="button" class="btn btn-icon btn-outline" data-act="down" title="Descendre" style="margin-top:20px; flex:none;">↓</button>
    `;
    const input = row.querySelector('[data-inp]');
    input.value = item.label || '';
    input.oninput = () => { item.label = input.value; commitChange('Modification du lien « ' + (item.id || '') + ' »', { debounce:true }); };
    input.addEventListener('blur', flushHistoryDebounce);
    attachFieldValidation(input, (v) => (v || '').trim() ? null : 'Le texte du lien ne peut pas être vide.', validators);
    row.querySelector('[data-act=up]').onclick = () => { if (i===0) return; [CONTENT.nav[i-1],CONTENT.nav[i]]=[CONTENT.nav[i],CONTENT.nav[i-1]]; renderNav(); commitChange('Réorganisation du menu'); };
    row.querySelector('[data-act=down]').onclick = () => { if (i===CONTENT.nav.length-1) return; [CONTENT.nav[i+1],CONTENT.nav[i]]=[CONTENT.nav[i],CONTENT.nav[i+1]]; renderNav(); commitChange('Réorganisation du menu'); };
    row.querySelector('[data-act=up]').disabled = (i===0);
    row.querySelector('[data-act=down]').disabled = (i===CONTENT.nav.length-1);
    list.appendChild(row);
  });
  setSectionValidators('nav', validators);
}

/* --------------------------- Couleurs / thème --------------------------- */
/* Champs groupés par usage, avec une explication concrète de où chaque
   couleur apparaît sur le site — pour comprendre en un coup d'œil quoi
   on modifie, sans avoir à deviner. */
const THEME_GROUPS = [
  {
    title: 'Fonds',
    desc: 'Les couleurs derrière le contenu.',
    fields: [
      { key:'paper', label:'Fond général', usage:'Le fond de la quasi-totalité des pages du site.' },
      { key:'paperSoft', label:'Fond des sections alternées', usage:'Sections en alternance claire, fond des cartes (ex. cours &amp; tarifs).' },
      { key:'pineDark', label:'Fonds foncés', usage:'Le grand bloc d\u2019accueil (héros) et le pied de page.' }
    ]
  },
  {
    title: 'Textes',
    desc: 'Les couleurs du texte, à adapter à leur fond.',
    fields: [
      { key:'ink', label:'Texte principal', usage:'Titres et texte courant, sur fond clair.' },
      { key:'olive', label:'Texte secondaire', usage:'Sous-titres, légendes, petits labels en majuscules.' },
      { key:'cream', label:'Texte clair', usage:'Texte affiché sur les fonds foncés (héros, pied de page).' }
    ]
  },
  {
    title: 'Accents',
    desc: 'Les touches de couleur qui font ressortir l\u2019essentiel.',
    fields: [
      { key:'gold', label:'Accent doré', usage:'Boutons principaux, mots mis en valeur (ex. <em>sur-mesure</em>).' },
      { key:'goldLight', label:'Beige clair lumineux', usage:'Dégradé des boutons dorés, petits badges au-dessus des titres.' },
      { key:'pine', label:'Accent secondaire', usage:'Icônes et détails discrets sur les cartes.' }
    ]
  }
];
const THEME_FIELDS = THEME_GROUPS.flatMap(g => g.fields);

/* --------------------------- Préréglages de palette --------------------------- */
/* Chaque préréglage fournit les 9 couleurs d'un coup. Toujours appliqué
   via commitChange (donc annulable avec « Annuler ») — rien n'est perdu
   si le résultat ne plaît pas. Regroupés par ambiance pour s'y retrouver
   parmi un choix plus large. */
const THEME_PRESETS = [
  { name:'Doré (actuel)', category:'Chaleureux & doré', theme:{ ink:'#3d352c', paper:'#fbf7ec', paperSoft:'#f4ecd8', pineDark:'#8c8068', pine:'#b8a47c', olive:'#8f8268', gold:'#ab8f66', goldLight:'#ddd0b0', cream:'#fffaf0' } },
  { name:'Terracotta', category:'Chaleureux & doré', theme:{ ink:'#3c2a22', paper:'#fbf1e7', paperSoft:'#f3e0cd', pineDark:'#9c5a3c', pine:'#c17b52', olive:'#8a5f45', gold:'#c17a4a', goldLight:'#e8b98f', cream:'#fff6ec' } },
  { name:'Miel', category:'Chaleureux & doré', theme:{ ink:'#3d2f1c', paper:'#fdf8ec', paperSoft:'#f6ebd2', pineDark:'#8a6a2f', pine:'#c9a04c', olive:'#8a7452', gold:'#d1a13a', goldLight:'#ecd8a0', cream:'#fffaf0' } },
  { name:'Cannelle', category:'Chaleureux & doré', theme:{ ink:'#3a2620', paper:'#fbf2ea', paperSoft:'#f2e0d2', pineDark:'#7a4a35', pine:'#b97a55', olive:'#8a6152', gold:'#b56a3f', goldLight:'#e3b58f', cream:'#fff6ef' } },

  { name:'Forêt', category:'Naturel & végétal', theme:{ ink:'#2e3527', paper:'#f6f7ec', paperSoft:'#e9ecd9', pineDark:'#3f5738', pine:'#6f8f5c', olive:'#5c6b4e', gold:'#8ea05a', goldLight:'#cdd9ab', cream:'#f8faf0' } },
  { name:'Sauge', category:'Naturel & végétal', theme:{ ink:'#333527', paper:'#f6f7ee', paperSoft:'#e8ecd7', pineDark:'#5c6b45', pine:'#8a9c68', olive:'#6c7358', gold:'#96a068', goldLight:'#d3dab0', cream:'#f9faf1' } },
  { name:'Eucalyptus', category:'Naturel & végétal', theme:{ ink:'#20342f', paper:'#f2f8f5', paperSoft:'#dcece4', pineDark:'#2f5c50', pine:'#5c9080', olive:'#4e6b60', gold:'#5fa08d', goldLight:'#a9d3c4', cream:'#f5fbf8' } },

  { name:'Ardoise', category:'Frais & minéral', theme:{ ink:'#2b3238', paper:'#f3f5f6', paperSoft:'#e4e9ec', pineDark:'#48565f', pine:'#7891a0', olive:'#5a6a72', gold:'#8fa6ae', goldLight:'#c9d7dc', cream:'#f8fbfc' } },
  { name:'Brume', category:'Frais & minéral', theme:{ ink:'#333638', paper:'#f5f6f7', paperSoft:'#e6e9eb', pineDark:'#5c6b72', pine:'#8ea0a8', olive:'#636f74', gold:'#7f97a0', goldLight:'#c3d3d8', cream:'#f9fafb' } },
  { name:'Glacier', category:'Frais & minéral', theme:{ ink:'#26333d', paper:'#f2f7fb', paperSoft:'#e0edf5', pineDark:'#3c6a8c', pine:'#6ea0c2', olive:'#526779', gold:'#5c93bb', goldLight:'#aed0e8', cream:'#f7fbfd' } },

  { name:'Lavande', category:'Élégant & doux', theme:{ ink:'#332e3d', paper:'#f8f5fb', paperSoft:'#ece4f3', pineDark:'#655a7c', pine:'#8f7fac', olive:'#6c6180', gold:'#9c85b8', goldLight:'#d8c9e8', cream:'#fbf8fd' } },
  { name:'Rosé poudré', category:'Élégant & doux', theme:{ ink:'#3d2e30', paper:'#fbf3f2', paperSoft:'#f4e2df', pineDark:'#8c5c62', pine:'#c1878c', olive:'#8a6669', gold:'#c17a86', goldLight:'#e8bcc2', cream:'#fff6f5' } },
  { name:'Prune', category:'Élégant & doux', theme:{ ink:'#332632', paper:'#f8f3f8', paperSoft:'#ecdfec', pineDark:'#5c3f5e', pine:'#8a6a8c', olive:'#68566a', gold:'#9c5f8f', goldLight:'#d2a8c9', cream:'#fbf6fb' } },

  { name:'Corail', category:'Vif & contrasté', theme:{ ink:'#3d2a26', paper:'#fdf3ee', paperSoft:'#f6ddd0', pineDark:'#a3492f', pine:'#e07c52', olive:'#8a5b48', gold:'#e8623d', goldLight:'#f4ab86', cream:'#fff8f3' } },
  { name:'Émeraude', category:'Vif & contrasté', theme:{ ink:'#1e332a', paper:'#f1f8f3', paperSoft:'#dceee1', pineDark:'#1f5c3f', pine:'#3f9066', olive:'#3f6752', gold:'#2f9c68', goldLight:'#8fd6ac', cream:'#f5fbf6' } }
];
const THEME_PRESET_CATEGORY_ORDER = ['Chaleureux & doré', 'Naturel & végétal', 'Frais & minéral', 'Élégant & doux', 'Vif & contrasté'];

function renderThemePresets(){
  const wrap = $('#themePresets');
  wrap.innerHTML = '';
  const swatchKeys = ['pineDark','gold','paperSoft','cream'];
  THEME_PRESET_CATEGORY_ORDER.forEach(cat => {
    const presetsInCat = THEME_PRESETS.filter(p => p.category === cat);
    if (!presetsInCat.length) return;
    const group = document.createElement('div');
    group.className = 'theme-preset-group';
    group.innerHTML = `<div class="theme-preset-group-title">${cat}</div>`;
    const row = document.createElement('div');
    row.className = 'theme-presets';
    presetsInCat.forEach(preset => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'theme-preset';
      const isCurrent = THEME_FIELDS.every(f => (CONTENT.theme[f.key] || '').toLowerCase() === preset.theme[f.key].toLowerCase());
      if (isCurrent) btn.classList.add('is-current');
      btn.innerHTML =
        '<span class="theme-preset-swatch">' +
          swatchKeys.map(k => '<span style="background:' + preset.theme[k] + '"></span>').join('') +
        '</span>' +
        '<span>' + preset.name + '</span>';
      btn.addEventListener('click', () => {
        CONTENT.theme = Object.assign({}, CONTENT.theme, preset.theme);
        renderTheme();
        applyTheme(CONTENT.theme);
        commitChange('Application de la palette « ' + preset.name + ' »');
        showToast('Palette « ' + preset.name + ' » appliquée.');
      });
      row.appendChild(btn);
    });
    group.appendChild(row);
    wrap.appendChild(group);
  });
}
function hexToRgb(hex){
  if (!hex) return null;
  const m = hex.replace('#','').match(/.{1,2}/g);
  if (!m || m.length < 3) return null;
  return m.map(h => parseInt(h,16)).join(',');
}
function applyTheme(t){
  if (!t) return;
  const root = document.documentElement.style;
  const cssVarMap = { ink:'--ink', paper:'--paper', paperSoft:'--paper-soft', pineDark:'--pine-dark', pine:'--pine', olive:'--olive', gold:'--gold', goldLight:'--gold-light', cream:'--cream' };
  Object.entries(cssVarMap).forEach(([key, cssVar]) => { if (t[key]) root.setProperty(cssVar, t[key]); });
  if (t.ink) root.setProperty('--ink-rgb', hexToRgb(t.ink));
  if (t.pineDark) root.setProperty('--pine-dark-rgb', hexToRgb(t.pineDark));
  if (t.cream) root.setProperty('--cream-rgb', hexToRgb(t.cream));
  if (t.paper) root.setProperty('--paper-rgb', hexToRgb(t.paper));
}
function setThemeHighlight(key, on){
  const preview = $('#themePreviewCard');
  if (!preview) return;
  $$('[data-role="' + key + '"]', preview).forEach(el => el.classList.toggle('tp-focus', on));
}

/* --------------------------- Picker précis (SV + teinte) --------------------------- */
/* Un vrai sélecteur continu (carré saturation/luminosité + réglette de
   teinte), en plus des couleurs rapides ci-dessous et des préréglages
   de palette complets (THEME_PRESETS) — pour choisir une couleur au
   pixel près plutôt que de piocher uniquement parmi des valeurs figées. */
const CP_QUICK_COLORS = [
  '#ffffff', '#f5f2ea', '#e2ddce', '#b8a47c', '#8f8268', '#3d352c', '#1c1712', '#000000',
  '#ab8f66', '#c17a4a', '#e0b25a', '#8ea05a', '#3f5738', '#6f8f5c', '#7891a0', '#48565f',
  '#9c85b8', '#655a7c', '#c1516b', '#8c3b4a', '#5a6a72', '#2b3238', '#d9c9a3', '#8a5f45'
];
function hexToHsv(hex){
  hex = (hex || '').replace('#', '');
  if (!/^[0-9a-fA-F]{6}$/.test(hex)) hex = '000000';
  const r = parseInt(hex.slice(0,2),16)/255, g = parseInt(hex.slice(2,4),16)/255, b = parseInt(hex.slice(4,6),16)/255;
  const max = Math.max(r,g,b), min = Math.min(r,g,b), d = max - min;
  let h = 0;
  if (d !== 0){
    if (max === r) h = ((g - b) / d) % 6;
    else if (max === g) h = (b - r) / d + 2;
    else h = (r - g) / d + 4;
    h *= 60;
    if (h < 0) h += 360;
  }
  const s = max === 0 ? 0 : d / max;
  const v = max;
  return { h, s, v };
}
function hsvToHex(h, s, v){
  const c = v * s, x = c * (1 - Math.abs((h / 60) % 2 - 1)), m = v - c;
  let r=0, g=0, b=0;
  if (h < 60){ r=c; g=x; b=0; }
  else if (h < 120){ r=x; g=c; b=0; }
  else if (h < 180){ r=0; g=c; b=x; }
  else if (h < 240){ r=0; g=x; b=c; }
  else if (h < 300){ r=x; g=0; b=c; }
  else { r=c; g=0; b=x; }
  const toHex = (n) => Math.round((n + m) * 255).toString(16).padStart(2, '0');
  return '#' + toHex(r) + toHex(g) + toHex(b);
}
(function initColorPicker(){
  const popover = document.getElementById('colorPickerPopover');
  if (!popover) return; // markup absent (ne devrait pas arriver)
  const svEl = document.getElementById('cpSV');
  const svThumb = document.getElementById('cpSVThumb');
  const hueSlider = document.getElementById('cpHue');
  const hexInput = document.getElementById('cpHexInput');
  const currentSwatch = document.getElementById('cpCurrentSwatch');
  const quickGrid = document.getElementById('cpQuickGrid');

  quickGrid.innerHTML = CP_QUICK_COLORS.map(c =>
    `<button type="button" class="cp-quick-swatch" style="background:${c}" data-hex="${c}" aria-label="${c}"></button>`
  ).join('');

  let state = { h:0, s:0, v:0 };
  let anchorEl = null, onChange = null, onCommit = null, draggingSV = false;

  function currentHex(){ return hsvToHex(state.h, state.s, state.v); }

  function paint(fromHex){
    svEl.style.background = 'hsl(' + state.h + ',100%,50%)';
    svThumb.style.left = (state.s * 100) + '%';
    svThumb.style.top = ((1 - state.v) * 100) + '%';
    hueSlider.value = Math.round(state.h);
    currentSwatch.style.background = currentHex();
    if (!fromHex) hexInput.value = currentHex().slice(1).toUpperCase();
  }

  function emit(){ if (onChange) onChange(currentHex()); }
  function commit(){ if (onCommit) onCommit(currentHex()); }

  function setFromClientXY(clientX, clientY){
    const rect = svEl.getBoundingClientRect();
    const x = Math.min(Math.max(clientX - rect.left, 0), rect.width);
    const y = Math.min(Math.max(clientY - rect.top, 0), rect.height);
    state.s = rect.width ? x / rect.width : 0;
    state.v = rect.height ? 1 - (y / rect.height) : 0;
    paint();
    emit();
  }

  svEl.addEventListener('pointerdown', (e) => {
    draggingSV = true;
    svEl.setPointerCapture(e.pointerId);
    setFromClientXY(e.clientX, e.clientY);
  });
  svEl.addEventListener('pointermove', (e) => { if (draggingSV) setFromClientXY(e.clientX, e.clientY); });
  svEl.addEventListener('pointerup', () => { if (draggingSV){ draggingSV = false; commit(); } });
  svEl.addEventListener('pointercancel', () => { draggingSV = false; });

  hueSlider.addEventListener('input', () => {
    state.h = Number(hueSlider.value);
    paint();
    emit();
  });
  hueSlider.addEventListener('change', commit);

  hexInput.addEventListener('input', () => {
    const clean = hexInput.value.replace(/[^0-9a-fA-F]/g, '').slice(0, 6);
    if (clean.length === 6){
      state = hexToHsv(clean);
      paint(true);
      emit();
    }
  });
  hexInput.addEventListener('blur', commit);
  hexInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') hexInput.blur(); });

  quickGrid.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-hex]');
    if (!btn) return;
    state = hexToHsv(btn.getAttribute('data-hex'));
    paint();
    emit();
    commit();
  });

  function position(){
    if (!anchorEl) return;
    popover.style.visibility = 'hidden';
    popover.classList.add('is-open');
    const rect = anchorEl.getBoundingClientRect();
    const pw = popover.offsetWidth, ph = popover.offsetHeight;
    const vw = window.innerWidth, vh = window.innerHeight;
    let left = rect.left;
    let top = rect.bottom + 8;
    if (left + pw > vw - 12) left = vw - pw - 12;
    if (left < 12) left = 12;
    if (top + ph > vh - 12) top = rect.top - ph - 8;
    if (top < 12) top = 12;
    popover.style.left = left + 'px';
    popover.style.top = top + 'px';
    popover.style.visibility = 'visible';
  }

  window.openColorPicker = function(anchor, initialHex, changeCb, commitCb){
    if (anchorEl && anchorEl !== anchor) anchorEl.classList.remove('is-open');
    anchorEl = anchor;
    onChange = changeCb;
    onCommit = commitCb;
    state = hexToHsv(initialHex);
    paint();
    anchorEl.classList.add('is-open');
    position();
    requestAnimationFrame(() => hexInput.focus({ preventScroll:true }));
  };
  window.closeColorPicker = function(){
    if (!popover.classList.contains('is-open')) return;
    popover.classList.remove('is-open');
    if (anchorEl) anchorEl.classList.remove('is-open');
    anchorEl = null; onChange = null; onCommit = null;
  };
  document.addEventListener('pointerdown', (e) => {
    if (!popover.classList.contains('is-open')) return;
    if (popover.contains(e.target) || (anchorEl && anchorEl.contains(e.target))) return;
    closeColorPicker();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && popover.classList.contains('is-open')) closeColorPicker();
  });
  window.addEventListener('scroll', () => closeColorPicker(), true);
  window.addEventListener('resize', () => { if (popover.classList.contains('is-open')) position(); });
})();

function renderTheme(){
  const body = $('#themeBody');
  body.innerHTML = '';
  const validators = [];
  THEME_GROUPS.forEach(group => {
    const groupEl = document.createElement('div');
    groupEl.className = 'theme-group';
    groupEl.innerHTML = `
      <div class="theme-group-head" data-role="head" tabindex="0" role="button" aria-expanded="true">
        <div class="theme-group-head-text">
          <div class="theme-group-title">${group.title}</div>
          <div class="theme-group-desc">${group.desc}</div>
        </div>
        <svg class="theme-group-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 9l6 6 6-6"/></svg>
      </div>
      <div class="theme-group-body" data-role="body"></div>
    `;
    const fieldsGrid = document.createElement('div');
    fieldsGrid.className = 'theme-fields-grid';
    group.fields.forEach(f => {
      const card = document.createElement('div');
      card.className = 'theme-field-card';
      card.innerHTML = `<label>${f.label}</label><div class="theme-usage">${f.usage}</div><div class="color-row"><button type="button" class="color-swatch" data-c-btn aria-label="Choisir la couleur « ${f.label} » précisément"></button><input type="text" data-t placeholder="#ab8f66" spellcheck="false"><button type="button" class="btn btn-icon btn-outline theme-field-touch-toggle" data-touch-highlight title="Voir où « ${f.label} » s'applique dans l'aperçu" aria-label="Voir où « ${f.label} » s'applique dans l'aperçu" aria-pressed="false">👁</button><button type="button" class="btn btn-icon btn-outline theme-field-restore" data-restore-field title="Restaurer cette couleur à la valeur actuellement en ligne" aria-label="Restaurer « ${f.label} » à la valeur actuellement en ligne" style="display:none;">↺</button></div>`;
      const swatchBtn = card.querySelector('[data-c-btn]');
      const textInp = card.querySelector('[data-t]');
      const restoreBtn = card.querySelector('[data-restore-field]');
      const touchToggle = card.querySelector('[data-touch-highlight]');
      const val = CONTENT.theme[f.key] || '#000000';
      const safeVal = /^#[0-9a-fA-F]{6}$/.test(val) ? val : '#000000';
      swatchBtn.style.background = safeVal;
      textInp.value = CONTENT.theme[f.key] || '';
      function updateRestoreBtn(){
        const pristineVal = PRISTINE_CONTENT && PRISTINE_CONTENT.theme ? PRISTINE_CONTENT.theme[f.key] : undefined;
        const differs = pristineVal !== undefined && pristineVal.toLowerCase() !== (CONTENT.theme[f.key] || '').toLowerCase();
        restoreBtn.style.display = differs ? 'inline-flex' : 'none';
      }
      updateRestoreBtn();
      restoreBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        if (!PRISTINE_CONTENT || !PRISTINE_CONTENT.theme) return;
        const ok = applyRowRevert({ kind:'field', section:'theme', field:f.key });
        if (!ok) return;
        renderTheme();
        applyTheme(CONTENT.theme);
        commitChange('Restauration de la couleur « ' + f.label + ' »');
        scheduleSectionBadgesUpdate(true);
        showToast('Couleur « ' + f.label + ' » restaurée à la valeur en ligne.');
      });
      function commitColor(v){
        CONTENT.theme[f.key] = v;
        applyTheme(CONTENT.theme);
        commitChange('Modification de la couleur « ' + f.label + ' »', { debounce:true });
        renderThemePresets();
        updateRestoreBtn();
      }
      textInp.oninput = () => { if (/^#[0-9a-fA-F]{6}$/.test(textInp.value)) swatchBtn.style.background = textInp.value; commitColor(textInp.value); };
      textInp.addEventListener('blur', flushHistoryDebounce);
      attachFieldValidation(textInp, (v) => {
        const t = (v || '').trim();
        if (!t) return 'Ce champ est obligatoire.';
        return /^#[0-9a-fA-F]{6}$/.test(t) ? null : 'Format hexadécimal invalide, ex. #ab8f66.';
      }, validators);
      // Aperçu ciblé : survol ou focus du champ met en valeur la zone
      // correspondante dans la maquette miniature ci-dessus. Sur souris,
      // ça reste actif tant qu'on reste sur le champ, ce qui permet de
      // garder l'aperçu allumé pendant qu'on ajuste la couleur.
      const activate = () => { card.classList.add('is-active'); setThemeHighlight(f.key, true); };
      const deactivate = () => {
        if (touchToggle.classList.contains('is-active')) return; // le tap prime sur le survol
        card.classList.remove('is-active'); setThemeHighlight(f.key, false);
      };
      card.addEventListener('mouseenter', activate);
      card.addEventListener('mouseleave', deactivate);
      swatchBtn.addEventListener('focus', activate);
      textInp.addEventListener('focus', activate);
      swatchBtn.addEventListener('blur', deactivate);
      textInp.addEventListener('blur', deactivate);
      // Sur téléphone/tactile, le survol n'existe pas : ce bouton permet
      // d'allumer/éteindre la même surbrillance d'un tap, et reste actif
      // (pas besoin de garder le doigt appuyé) — masqué sur souris/trackpad
      // où le survol suffit déjà (voir la media query hover:none).
      touchToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const nowActive = !touchToggle.classList.contains('is-active');
        // Un seul champ mis en avant à la fois : on éteint les autres.
        $$('.theme-field-touch-toggle.is-active').forEach(btn => {
          if (btn !== touchToggle){
            btn.classList.remove('is-active');
            btn.setAttribute('aria-pressed', 'false');
            btn.closest('.theme-field-card').classList.remove('is-active');
          }
        });
        touchToggle.classList.toggle('is-active', nowActive);
        touchToggle.setAttribute('aria-pressed', String(nowActive));
        card.classList.toggle('is-active', nowActive);
        setThemeHighlight(f.key, nowActive);
      });
      // Le picker précis (saturation/teinte) s'ouvre au clic sur la
      // pastille — remplace le sélecteur natif du navigateur, peu
      // précis et inégal d'un appareil à l'autre.
      swatchBtn.addEventListener('click', () => {
        activate();
        openColorPicker(swatchBtn, textInp.value || safeVal, (hex) => {
          swatchBtn.style.background = hex;
          textInp.value = hex;
          commitColor(hex);
        }, () => { flushHistoryDebounce(); });
      });
      fieldsGrid.appendChild(card);
    });
    const groupBody = groupEl.querySelector('[data-role=body]');
    groupBody.appendChild(fieldsGrid);
    const groupHead = groupEl.querySelector('[data-role=head]');
    const toggleThisGroup = () => setThemeGroupOpen(groupEl, !groupEl.classList.contains('open'), true);
    groupHead.addEventListener('click', toggleThisGroup);
    groupHead.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' '){ e.preventDefault(); toggleThisGroup(); } });
    body.appendChild(groupEl);
    // Ouvert par défaut — replier reste un choix explicite de la personne,
    // utile surtout sur un écran peu haut où l'aperçu sort du cadre visible.
    setThemeGroupOpen(groupEl, true, false);
  });
  setSectionValidators('theme', validators);
  renderThemePresets();
}

/* Replie/déplie un groupe de couleurs (Fonds, Textes, Accents…) —
   même logique d'animation que setServiceCardOpen, adaptée ici pour
   ne pas dépendre d'un display:none/block sur toute la carte. */
function setThemeGroupOpen(groupEl, open, animate){
  const bodyEl = groupEl.querySelector('[data-role=body]');
  const headEl = groupEl.querySelector('[data-role=head]');
  if (!bodyEl) return;
  const isOpen = groupEl.classList.contains('open');
  if (open === isOpen) return;
  if (headEl) headEl.setAttribute('aria-expanded', String(open));
  if (open){
    groupEl.classList.add('open');
    bodyEl.style.display = 'block';
    if (!animate){ bodyEl.style.maxHeight = 'none'; bodyEl.style.opacity = '1'; return; }
    const target = bodyEl.scrollHeight;
    bodyEl.style.maxHeight = '0px';
    bodyEl.style.opacity = '0';
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        bodyEl.style.maxHeight = target + 'px';
        bodyEl.style.opacity = '1';
      });
    });
    const onEnd = (e) => {
      if (e.propertyName !== 'max-height') return;
      bodyEl.style.maxHeight = 'none';
      bodyEl.removeEventListener('transitionend', onEnd);
    };
    bodyEl.addEventListener('transitionend', onEnd);
  } else {
    if (!animate){
      groupEl.classList.remove('open');
      bodyEl.style.display = 'none';
      bodyEl.style.maxHeight = '0px';
      bodyEl.style.opacity = '0';
      return;
    }
    const current = bodyEl.scrollHeight;
    bodyEl.style.maxHeight = current + 'px';
    bodyEl.style.opacity = '1';
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        bodyEl.style.maxHeight = '0px';
        bodyEl.style.opacity = '0';
      });
    });
    const onEnd = (e) => {
      if (e.propertyName !== 'max-height') return;
      groupEl.classList.remove('open');
      bodyEl.style.display = 'none';
      bodyEl.removeEventListener('transitionend', onEnd);
    };
    bodyEl.addEventListener('transitionend', onEnd);
  }
}
$('#resetThemeBtn').addEventListener('click', async () => {
  const ok = await customConfirm({
    title: 'Réinitialiser les couleurs ?',
    message: 'Elles reviendront telles qu\u2019elles étaient au chargement de la page.',
    okText: 'Réinitialiser',
    cancelText: 'Annuler',
    icon: '🎨'
  });
  if (!ok) return;
  CONTENT.theme = JSON.parse(JSON.stringify(ORIGINAL_THEME));
  renderTheme();
  applyTheme(CONTENT.theme);
  commitChange('Réinitialisation des couleurs');
});

/* =====================================================================
   APERÇU DU SITE — charge le vrai index.html dans une iframe et lui
   injecte le contenu en cours d'édition (même non téléchargé), en
   interceptant son fetch('content.json') interne. Rien n'est modifié
   sur le vrai fichier index.html.
   ===================================================================== */
let INDEX_HTML_SOURCE = null;

async function ensureIndexSource(){
  if (INDEX_HTML_SOURCE) return INDEX_HTML_SOURCE;
  const res = await fetchWithTimeout('index.html', { cache: 'no-store' }, 15000);
  if (!res.ok) throw new Error('HTTP ' + res.status);
  INDEX_HTML_SOURCE = await res.text();
  return INDEX_HTML_SOURCE;
}

function buildPreviewDocument(){
  const jsonInner = JSON.stringify(JSON.stringify(CONTENT));
  let inner = '(function(){'
    + 'var DATA=' + jsonInner + ';'
    + 'var PARSED=JSON.parse(DATA);'
    + 'var originalFetch=window.fetch.bind(window);'
    + 'window.fetch=function(url,opts){'
    +   'if(typeof url==="string"&&url.indexOf("content.json")!==-1){'
    +     'return Promise.resolve(new Response(JSON.stringify(PARSED),{status:200,headers:{"Content-Type":"application/json"}}));'
    +   '}'
    +   'return originalFetch(url,opts);'
    + '};'
    + 'try{localStorage.removeItem("momoxdogs_content_cache_v1");}catch(e){}'
    + '})();';
  // Sécurité : si un texte édité contient la séquence "</script", on
  // l'échappe pour ne pas couper prématurément la balise <script> injectée
  // ci-dessous (le reste du fichier index.html n'est jamais touché).
  inner = inner.replace(/<\/script/gi, '<\\/script');
  const overrideScript = '<script>' + inner + '</scr' + 'ipt>';
  return INDEX_HTML_SOURCE.replace('<head>', '<head>' + overrideScript);
}

async function openPreview(){
  const btn = $('#previewBtn');
  const originalLabel = btn.textContent;
  btn.disabled = true;
  btn.textContent = 'Chargement…';
  try {
    await ensureIndexSource();
  } catch(err){
    showToast("Aperçu indisponible : impossible de charger index.html (" + err.message + "). Ouvre cette page via un serveur local, à côté d'index.html.", true);
    return;
  } finally {
    btn.disabled = false;
    btn.textContent = originalLabel;
  }
  $('#previewIframe').srcdoc = buildPreviewDocument();
  $('#previewOverlay').style.display = 'flex';
}
function closePreview(){
  $('#previewOverlay').style.display = 'none';
  $('#previewIframe').removeAttribute('srcdoc');
}
function refreshPreview(){
  if ($('#previewOverlay').style.display === 'none') return;
  $('#previewIframe').srcdoc = buildPreviewDocument();
}

$('#previewBtn').addEventListener('click', openPreview);
$('#previewCloseBtn').addEventListener('click', closePreview);
$('#previewRefreshBtn').addEventListener('click', refreshPreview);
$('#previewDesktopBtn').addEventListener('click', () => $('#previewFrameWrap').classList.remove('mobile'));
$('#previewMobileBtn').addEventListener('click', () => $('#previewFrameWrap').classList.add('mobile'));

/* =====================================================================
   GUIDE COMPLET — fiche explicative pas à pas, indépendante du chargement
   de content.json (accessible même si le fichier n'a pas pu être lu).
   ===================================================================== */
const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function openGuide(){
  const overlay = $('#guideOverlay');
  overlay.style.display = 'flex';
  overlay.scrollTop = 0;
  $('.guide-body').scrollTop = 0;
  // Double rAF : laisse le navigateur peindre l'etat display:flex/opacity:0
  // avant d'ajouter .show, sans quoi la transition d'entree serait sautee.
  requestAnimationFrame(() => requestAnimationFrame(() => overlay.classList.add('show')));
  updateGuideProgress();
  initGuideScrollFx();
}
function closeGuide(){
  const overlay = $('#guideOverlay');
  overlay.classList.remove('show');
  setTimeout(() => { overlay.style.display = 'none'; }, 320);
}

/* Jauge de lecture en haut du guide - reflete la position de scroll
   dans .guide-body. Mise a jour au defilement et a l'ouverture. */
function updateGuideProgress(){
  const body = $('.guide-body');
  const fill = $('#guideProgressFill');
  if (!body || !fill) return;
  const max = body.scrollHeight - body.clientHeight;
  const pct = max > 0 ? Math.min(100, Math.max(0, (body.scrollTop / max) * 100)) : 0;
  fill.style.width = pct + '%';
}

/* Sommaire actif + apparition progressive des etapes au scroll, et
   petite fete sur l'aide-memoire une fois les 4 cases cochees. Tout
   est initialise une seule fois, a la premiere ouverture du guide
   (les elements sont display:none avant ca, IntersectionObserver ne
   servirait a rien plus tot) ; sans JavaScript, tout resterait de
   toute facon parfaitement lisible (aucune classe gr-pre posee). */
let guideFxInitialized = false;
function initGuideScrollFx(){
  if (guideFxInitialized) return;
  guideFxInitialized = true;

  const body = $('.guide-body');
  if (body) body.addEventListener('scroll', updateGuideProgress, { passive: true });

  if (!prefersReducedMotion){
    const revealTargets = $$('.guide-step, #guide-checklist');
    revealTargets.forEach(el => el.classList.add('gr-pre'));
    const revealObs = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('gr-in');
        revealObs.unobserve(entry.target);
      });
    }, { root: body, threshold: .1, rootMargin: '0px 0px -8% 0px' });
    revealTargets.forEach(el => revealObs.observe(el));
  }

  const tocLinks = $$('.guide-toc a');
  const spyTargets = $$('.guide-step[id], #guide-checklist');
  if (tocLinks.length && spyTargets.length){
    const spyObs = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const link = tocLinks.find(a => a.getAttribute('href') === '#' + entry.target.id);
        if (!link) return;
        tocLinks.forEach(a => a.classList.remove('active'));
        link.classList.add('active');
      });
    }, { root: body, threshold: 0, rootMargin: '-15% 0px -70% 0px' });
    spyTargets.forEach(el => spyObs.observe(el));
  }

  const checklistBoxes = $$('#guide-checklist input[type=checkbox]');
  if (checklistBoxes.length){
    const checklistEl = $('#guide-checklist');
    const CHECKLIST_KEY = 'momoxGuideChecklistV1';
    let saved = [];
    try { saved = JSON.parse(localStorage.getItem(CHECKLIST_KEY) || '[]'); } catch(e){}
    checklistBoxes.forEach((box, i) => { if (saved[i]) box.checked = true; });
    const syncComplete = () => {
      const complete = checklistBoxes.every(b => b.checked);
      checklistEl.classList.toggle('is-complete', complete);
      return complete;
    };
    syncComplete();
    checklistBoxes.forEach(box => {
      box.addEventListener('change', () => {
        try { localStorage.setItem(CHECKLIST_KEY, JSON.stringify(checklistBoxes.map(b => b.checked))); } catch(e){}
        const wasComplete = checklistEl.classList.contains('is-complete');
        if (syncComplete() && !wasComplete && !prefersReducedMotion){
          checklistEl.classList.remove('celebrate');
          void checklistEl.offsetWidth;
          checklistEl.classList.add('celebrate');
        }
      });
    });
  }
}

/* Petite fete de pattes de chien affichee au moment ou une publication
   reussit - purement decorative, se retire toute seule. Sautee pour les
   personnes ayant demande moins d'animations. */
function celebratePublish(){
  if (prefersReducedMotion) return;
  const wrap = document.createElement('div');
  wrap.className = 'pub-burst';

  const glow = document.createElement('span');
  glow.className = 'pub-burst-glow';
  wrap.appendChild(glow);

  const center = document.createElement('span');
  center.className = 'pub-burst-center';
  center.textContent = '✅';
  wrap.appendChild(center);

  const total = 10;
  for (let i = 0; i < total; i++){
    const paw = document.createElement('span');
    paw.className = 'pub-burst-paw';
    paw.textContent = '🐾';
    const angle = (Math.PI * 2 * i) / total + (Math.random() * .4 - .2);
    const dist = 90 + Math.random() * 70;
    paw.style.setProperty('--tx', Math.cos(angle) * dist + 'px');
    paw.style.setProperty('--ty', Math.sin(angle) * dist + 'px');
    paw.style.setProperty('--tr', (Math.random() * 160 - 80) + 'deg');
    paw.style.animationDelay = (Math.random() * .12) + 's';
    wrap.appendChild(paw);
  }

  // Pluie de confettis en couleurs de la charte — couche festive
  // supplémentaire au-dessus du bouquet de pattes, purement décorative.
  const CONFETTI_COLORS = ['#ab8f66', '#ddd0b0', '#8c8068', '#5f7a4f', '#c1932c'];
  const confettiTotal = 22;
  for (let i = 0; i < confettiTotal; i++){
    const piece = document.createElement('span');
    piece.className = 'pub-confetti';
    piece.style.left = (Math.random() * 100) + 'vw';
    piece.style.background = CONFETTI_COLORS[i % CONFETTI_COLORS.length];
    piece.style.setProperty('--cx', (Math.random() * 140 - 70) + 'px');
    piece.style.setProperty('--cr', (Math.random() * 520 + 180) + 'deg');
    piece.style.animationDelay = (Math.random() * .3) + 's';
    piece.style.animationDuration = (1 + Math.random() * .5) + 's';
    wrap.appendChild(piece);
  }

  document.body.appendChild(wrap);
  setTimeout(() => wrap.remove(), 1900);
}
function setGuideCollapsed(collapsed){
  $('#guideOverlay').classList.toggle('guide-collapsed', collapsed);
  $('#guideCollapseBtn').setAttribute('aria-expanded', String(!collapsed));
  $('#guideCollapseBtn').title = collapsed ? 'Déplier l\u2019en-tête' : 'Replier l\u2019en-tête';
}
$('#guideBtn').addEventListener('click', openGuide);
$('#guideCloseBtn').addEventListener('click', closeGuide);
$('#guideCollapseBtn').addEventListener('click', () => {
  setGuideCollapsed(!$('#guideOverlay').classList.contains('guide-collapsed'));
});

/* Déconnexion : modale de confirmation custom (au lieu de confirm() natif),
   cohérente avec le reste de l'admin. Redirige seulement si confirmé. */
const logoutLinkEl = $('#logoutLink');
if (logoutLinkEl){
  logoutLinkEl.addEventListener('click', async (e) => {
    e.preventDefault();
    const ok = await customConfirm({
      title: 'Se déconnecter ?',
      message: 'Tu vas être déconnecté·e de l\u2019admin. Les modifications non publiées restent en brouillon sur cet appareil et te seront proposées à ta prochaine connexion.',
      okText: 'Se déconnecter',
      cancelText: 'Annuler',
      danger: true,
      icon: '🔒'
    });
    if (ok) window.location.href = logoutLinkEl.href;
  });
}
// Ferme le guide avec Échap, et referme d'abord le guide plutôt que
// l'aperçu si les deux étaient ouverts en même temps (ne devrait pas
// arriver en usage normal, mais reste cohérent si ça se produit).
document.addEventListener('keydown', (e) => {
  if (e.key !== 'Escape') return;
  if ($('#guideOverlay').style.display !== 'none') closeGuide();
});
// Un lien du sommaire fait défiler la fiche jusqu'à la bonne section
// (scroll-margin-top en CSS évite qu'elle se cache sous la barre du haut).
$$('.guide-toc a').forEach(a => {
  a.addEventListener('click', (e) => {
    e.preventDefault();
    const target = document.getElementById(a.getAttribute('href').slice(1));
    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
});

/* =====================================================================
   EXPORT
   ===================================================================== */
/* Vérifie tous les champs avant une action d'export. En cas d'erreur,
   ouvre le panneau du premier champ fautif, l'amène à l'écran et
   affiche un message clair plutôt que de laisser exporter des données
   incomplètes ou mal formées. Renvoie true si tout est valide. */
function checkFormBeforeExport(){
  const { ok, invalidCount, firstInvalidEl } = validateAll();
  if (!ok){
    showToast(
      invalidCount === 1
        ? 'Un champ contient une erreur — corrige-le avant de continuer (voir le message en rouge).'
        : invalidCount + ' champs contiennent une erreur — corrige-les avant de continuer (voir les messages en rouge).',
      true
    );
    if (firstInvalidEl) revealField(firstInvalidEl);
    return false;
  }
  return true;
}

function downloadJson(){
  if (!checkFormBeforeExport()) return;
  const blob = new Blob([JSON.stringify(CONTENT, null, 2)], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'content.json';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
  setDirty(false);
  // Le brouillon local n'est PAS effacé ici : on ne peut pas vérifier que
  // le téléchargement a réellement abouti (peu fiable sur certains
  // navigateurs mobiles). Il sert de filet de sécurité et disparaîtra de
  // lui-même la prochaine fois que le fichier en ligne correspondra.
  showToast('content.json téléchargé — remplace-le sur le serveur du site pour publier. Ta sauvegarde locale reste en secours jusque-là.');
}
$('#downloadBtn').addEventListener('click', downloadJson);

/* =====================================================================
   PUBLICATION EN LIGNE (enregistre content.json directement sur OVH
   via inc/save-content.php, qui garde une copie de l'ancienne version)

   Ordre des opérations :
   1. Validation des champs.
   2. Comparaison au contenu actuellement en ligne → modale à valider.
   3. Vérification que toutes les photos référencées existent sur le
      serveur → repère celles qui manquent (sans encore rien envoyer).
   4. Mot de passe demandé UNE SEULE FOIS ici, avant toute écriture sur
      le serveur — il sert ensuite à la fois pour l'envoi des photos
      manquantes (étape 5) et pour la publication du JSON (étape 6),
      sans nouveau prompt entre les deux.
   5. Si des photos manquent : modale de résolution (fournir les
      fichiers ou corriger le champ vers un fichier proche déjà
      présent) → envoi des photos avec le mot de passe de l'étape 4.
   6. Seulement si tout ce qui précède a réussi : publication du JSON,
      avec le même mot de passe.
   ===================================================================== */
async function publishContent(){
  if (!checkFormBeforeExport()) return;
  const btn = $('#publishBtn');
  const originalLabel = btn.textContent;
  btn.disabled = true;
  btn.textContent = 'Comparaison…';

  let liveContent = null;
  try {
    const res = await fetchWithTimeout('content.json', { cache: 'no-store' }, 15000);
    if (res.ok) liveContent = await readJsonSafe(res); // reste null si réponse illisible
  } catch(e){ /* pas grave : on publiera sans comparaison si impossible */ }

  btn.disabled = false;
  btn.textContent = originalLabel;

  // Recale au passage la référence des pastilles de section sur ce qui
  // est vraiment en ligne à cet instant (utile si quelqu'un d'autre a
  // publié entre-temps), même si la publication est ensuite annulée.
  if (liveContent){
    try {
      PRISTINE_CONTENT = normalizeContent(JSON.parse(JSON.stringify(liveContent)));
      scheduleSectionBadgesUpdate(true);
    } catch(e){ /* contenu en ligne non conforme : on garde l'ancienne référence */ }
  }

  const changes = liveContent ? diffContent(PRISTINE_CONTENT || liveContent, CONTENT) : [];

  if (liveContent && changes.length === 0){
    showToast('Aucune modification à publier — le contenu est déjà identique à la version en ligne.');
    return;
  }

  const proceed = await showDiffModal({
    icon: '🚀',
    title: 'Publier ces modifications ?',
    subtitle: liveContent
      ? 'Voici ce qui va changer sur le site en ligne par rapport à la version actuellement publiée.'
      : "Impossible de comparer à la version en ligne actuelle, mais tu peux tout de même publier.",
    changes,
    confirmLabel: '🚀 Publier en ligne',
    cancelLabel: 'Annuler'
  });
  if (!proceed) return;

  /* --------- Étape suivante : vérifier que les photos référencées existent --------- */
  const refs = collectImageReferences(CONTENT);
  let missing = [];
  if (refs.length){
    btn.disabled = true;
    btn.textContent = 'Vérification des photos…';
    try {
      const results = await checkImagesOnServer(refs.map(r => r.path));

      const invalid = results.filter(r => !r.valid);
      if (invalid.length){
        showToast('Chemin(s) d\u2019image invalide(s) : ' + invalid.map(i => i.path).join(', ') + ' — corrige le(s) champ(s) concerné(s) avant de publier.', true);
        return;
      }

      missing = results.filter(r => !r.exists).map(r => {
        const ref = refs.find(x => x.path === r.path);
        return { path: r.path, refs: ref ? ref.refs : [], suggestions: r.suggestions || [] };
      });
    } catch(e){
      showToast(e.message || 'Impossible de vérifier les photos sur le serveur — publication interrompue par précaution.', true);
      return;
    } finally {
      btn.disabled = false;
      btn.textContent = originalLabel;
    }
  }

  /* --------- Mot de passe demandé une seule fois, avant toute écriture
     sur le serveur (photos manquantes éventuelles + publication du
     JSON) — pas de second prompt pour les photos. --------- */
  const pwResult = await askPublishPassword();
  if (!pwResult.ok){
    showToast('Publication annulée — aucune modification n\u2019a été envoyée.', true);
    return;
  }

  if (missing.length){
    const photosOk = await showMissingPhotosModal(missing, pwResult.password);
    if (!photosOk){
      showToast('Publication annulée — aucune modification n\u2019a été envoyée.', true);
      return;
    }
    renderAll();
    applyTheme(CONTENT.theme);
    setDirty(true);
  }

  await doPublish(pwResult.password);
}

async function doPublish(confirmPassword){
  const btn = $('#publishBtn');
  const originalLabel = btn.textContent;
  btn.disabled = true;
  btn.textContent = 'Publication…';
  try {
    const res = await fetchWithTimeout('inc/save-content.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ content: CONTENT, confirm_password: confirmPassword })
    }, 30000);
    const data = await readJsonSafe(res);

    if (res.status === 401){
      showToast((data && data.message) || 'Session expirée — reconnecte-toi (recharge la page).', true);
      return;
    }
    if (res.status === 403){
      showToast((data && data.message) || 'Mot de passe de confirmation incorrect — publication refusée. Rien n\u2019a été modifié.', true);
      return;
    }
    if (res.status === 423){
      showToast((data && data.message) || 'Trop de tentatives de mot de passe — réessaie dans quelques secondes.', true);
      return;
    }
    if (!res.ok || !data || !data.ok){
      showToast((data && data.message) || ('Échec de la publication (serveur : ' + res.status + '). Rien ne devrait avoir été modifié — réessaie, et si le problème persiste, vérifie le fichier content.json directement sur le serveur.'), true);
      return;
    }

    setDirty(false);
    try { localStorage.removeItem(DRAFT_KEY); } catch(e){}
    PRISTINE_CONTENT = JSON.parse(JSON.stringify(CONTENT));
    scheduleSectionBadgesUpdate(true);
    // Une publication crée une nouvelle sauvegarde et peut rendre
    // d'anciennes photos orphelines (champ image changé) — les deux
    // panneaux concernés se rafraîchiront automatiquement (tout de
    // suite s'ils sont déjà ouverts, sinon à la prochaine ouverture).
    invalidateBackups();
    invalidateUnusedPhotos();
    invalidateServerImages();
    celebratePublish();
    showToast(
      data.backup
        ? 'Site mis à jour ✓ — l\u2019ancienne version a été sauvegardée sous ' + data.backup
        : 'Site mis à jour ✓'
    );
  } catch(e){
    showToast(e.message || 'Impossible de contacter le serveur pour publier. Vérifie ta connexion et réessaie.', true);
  } finally {
    btn.disabled = false;
    btn.textContent = originalLabel;
  }
}
$('#publishBtn').addEventListener('click', publishContent);

/* =====================================================================
   PHOTOS RÉFÉRENCÉES — recensement dans CONTENT, vérification côté
   serveur, et résolution des photos manquantes avant publication.
   ===================================================================== */

/* Recense tous les chemins d'image utilisés dans le contenu, avec pour
   chacun la liste des endroits qui l'utilisent (`refs`), chaque entrée
   exposant un `set(nouveauChemin)` qui met à jour CONTENT en place. */
function collectImageReferences(content){
  const map = new Map();
  function reg(path, label, setter){
    const v = (path || '').trim();
    if (!v) return;
    if (!map.has(v)) map.set(v, { path: v, refs: [] });
    map.get(v).refs.push({ label, set: setter });
  }
  if (content.images){
    reg(content.images.hero, "Image d'accueil", (np) => { content.images.hero = np; });
    reg(content.images.contact, 'Fond de la section contact', (np) => { content.images.contact = np; });
    reg(content.images.portrait, 'Portrait', (np) => { content.images.portrait = np; });
  }
  (content.services || []).forEach((s, i) => {
    reg(s.image, 'Cours « ' + (s.titre || ('#' + (i + 1))) + ' »', (np) => { s.image = np; });
  });
  (content.galerie || []).forEach((g, i) => {
    reg(g.src, 'Galerie — photo ' + (i + 1), (np) => { g.src = np; });
  });
  if (content.seo) reg(content.seo.shareImage, 'Image de partage (SEO)', (np) => { content.seo.shareImage = np; });
  return Array.from(map.values());
}

async function checkImagesOnServer(paths){
  if (!paths.length) return [];
  const res = await fetchWithTimeout('inc/check-images.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
    body: JSON.stringify({ paths })
  }, 20000);
  if (res.status === 401) throw new Error('Session expirée — recharge la page pour te reconnecter.');
  const data = await readJsonSafe(res);
  if (!res.ok || !data || !data.ok) throw new Error((data && data.message) || 'Impossible de vérifier les photos sur le serveur.');
  return data.results;
}

/* --------------------------- Correspondance de fichiers --------------------------- */
function fileExt(name){ const m = /\.([A-Za-z0-9]+)$/.exec(name || ''); return m ? m[1].toLowerCase() : ''; }
function slugifyBase(name){
  const base = (name || '').replace(/\.[^.]+$/, '');
  return base.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
}
function levenshtein(a, b){
  const m = a.length, n = b.length;
  if (!m) return n; if (!n) return m;
  const dp = [];
  for (let i = 0; i <= m; i++){ dp.push(new Array(n + 1).fill(0)); dp[i][0] = i; }
  for (let j = 0; j <= n; j++) dp[0][j] = j;
  for (let i = 1; i <= m; i++){
    for (let j = 1; j <= n; j++){
      dp[i][j] = a[i-1] === b[j-1] ? dp[i-1][j-1] : 1 + Math.min(dp[i-1][j-1], dp[i-1][j], dp[i][j-1]);
    }
  }
  return dp[m][n];
}

let PM_SLOTS = [];
let PM_UNMATCHED = [];
let PM_RETRY_HANDLER = null;

/* --------------------------- Aperçus miniatures --------------------------- */
/* Un object URL par emplacement, recréé uniquement si le fichier
   associé change, et libéré dès qu'il n'est plus utile — pour ne pas
   accumuler de fuites mémoire au fil des essais/erreurs/retraits. */
function pmSlotFile(slot){
  if (slot.pending) return slot.pending.file;
  if (slot.resolution && slot.resolution.type === 'upload') return slot.resolution.file;
  return null;
}
function pmThumbUrl(slot){
  const file = pmSlotFile(slot);
  if (!file){ pmRevokeThumb(slot); return null; }
  if (slot._thumbFile !== file){
    pmRevokeThumb(slot);
    slot._thumbUrl = URL.createObjectURL(file);
    slot._thumbFile = file;
  }
  return slot._thumbUrl;
}
function pmRevokeThumb(slot){
  if (slot._thumbUrl){ URL.revokeObjectURL(slot._thumbUrl); slot._thumbUrl = null; slot._thumbFile = null; }
}
function formatFileSize(bytes){
  if (typeof bytes !== 'number') return '';
  if (bytes < 1024) return bytes + ' o';
  if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' Ko';
  return (bytes / (1024 * 1024)).toFixed(1) + ' Mo';
}

/* Associe un fichier à un emplacement : correspondance directe si le
   nom ET l'extension correspondent exactement, sinon place le fichier
   « en attente de confirmation » avec le motif (extension différente,
   nom proche, ou les deux) plutôt que de l'assigner silencieusement. */
function applyFileToSlot(slot, file, reason){
  const extFile = fileExt(file.name);
  const extSlot = fileExt(slot.path);
  if (reason === 'exact'){
    slot.resolution = { type: 'upload', file, finalPath: slot.path };
    slot.pending = null;
  } else {
    const finalPath = extFile === extSlot ? slot.path : slot.path.replace(/\.[A-Za-z0-9]+$/, '.' + extFile);
    slot.pending = { file, finalPath, reason, extFile, extSlot };
    slot.resolution = null;
  }
}
function onManualFileChosen(slot, file){
  const extFile = fileExt(file.name), extSlot = fileExt(slot.path);
  applyFileToSlot(slot, file, extFile === extSlot ? 'exact' : 'extension');
  renderPhotoSlots();
}
function useSuggestion(slot, suggestion){
  slot.resolution = { type: 'existing', finalPath: suggestion.file };
  slot.pending = null;
  renderPhotoSlots();
}

/* Répartit une liste de fichiers déposés/choisis sur les emplacements
   encore libres, par ordre de confiance décroissante (nom+extension
   identiques, puis même nom mais extension différente, puis nom très
   proche à ±2 caractères). Les fichiers qui ne correspondent à aucun
   emplacement restent disponibles pour une association manuelle. */
function autoMatchFiles(files, slots){
  const unmatched = [];
  files.forEach(file => {
    const slugFile = slugifyBase(file.name);
    const extFile = fileExt(file.name);
    let best = null, bestScore = Infinity, bestReason = null;
    slots.forEach(slot => {
      if (slot.resolution || slot.pending || slot._claimedFile) return;
      const slugSlot = slugifyBase(diffImgBase(slot.path));
      const extSlot = fileExt(slot.path);
      if (slugFile === slugSlot && extFile === extSlot){
        if (0 < bestScore){ best = slot; bestScore = 0; bestReason = 'exact'; }
      } else if (slugFile === slugSlot && extFile !== extSlot){
        if (1 < bestScore){ best = slot; bestScore = 1; bestReason = 'extension'; }
      } else {
        const dist = levenshtein(slugFile, slugSlot);
        if (dist > 0 && dist <= 2 && (2 + dist) < bestScore){
          best = slot; bestScore = 2 + dist; bestReason = (extFile === extSlot ? 'similar' : 'similar+extension');
        }
      }
    });
    if (best){
      best._claimedFile = file;
      best._claimedReason = bestReason;
    } else {
      unmatched.push(file);
    }
  });
  slots.forEach(slot => {
    if (slot._claimedFile){
      applyFileToSlot(slot, slot._claimedFile, slot._claimedReason);
      delete slot._claimedFile;
      delete slot._claimedReason;
    }
  });
  return unmatched;
}

function handleIncomingPhotoFiles(files){
  const imageFiles = filterAcceptableImageFiles(files);
  if (!imageFiles.length){
    showToast(IMAGE_ACCEPT_HINT, true);
    return;
  }
  const { ok, oversized } = splitOversizedFiles(imageFiles);
  warnOversizedFiles(oversized);
  if (!ok.length) return;
  const unmatched = autoMatchFiles(ok, PM_SLOTS);
  PM_UNMATCHED.push(...unmatched);
  renderPhotoSlots();
}

function renderUnmatchedPhotoFiles(){
  const wrap = $('#pmUnmatchedWrap');
  const list = $('#pmUnmatched');
  if (!PM_UNMATCHED.length){ wrap.style.display = 'none'; list.innerHTML = ''; return; }
  wrap.style.display = 'block';
  list.innerHTML = '';
  PM_UNMATCHED.forEach((file, idx) => {
    const availableSlots = PM_SLOTS.filter(s => !s.resolution && !s.pending);
    const row = document.createElement('div');
    row.className = 'pm-unmatched-item';
    const options = availableSlots.map(s => `<option value="${escapeHtml(s.path)}">${escapeHtml(diffImgBase(s.path))}</option>`).join('');
    row.innerHTML = `<span>📄 ${escapeHtml(file.name)}</span>` +
      (availableSlots.length
        ? `<select><option value="">— associer à —</option>${options}</select>`
        : `<span class="desc">Aucun emplacement disponible</span>`);
    const select = row.querySelector('select');
    if (select){
      select.addEventListener('change', () => {
        if (!select.value) return;
        const slot = PM_SLOTS.find(s => s.path === select.value);
        if (slot){
          onManualFileChosen(slot, file);
          PM_UNMATCHED.splice(idx, 1);
          renderPhotoSlots();
        }
      });
    }
    list.appendChild(row);
  });
}

function renderPhotoSlots(){
  const container = $('#pmSlots');
  container.innerHTML = '';
  PM_SLOTS.forEach(slot => {
    const resolved = !!slot.resolution;
    const pending = !!slot.pending;
    const uploading = !!slot.uploading;
    const hasError = !!slot.error;
    const card = document.createElement('div');
    card.className = 'pm-slot' +
      (uploading ? ' is-uploading' : (hasError ? ' is-pending' : (resolved ? ' is-resolved' : (pending ? ' is-pending' : ''))));

    const thumbUrl = pmThumbUrl(slot);
    if (thumbUrl) card.classList.add('has-thumb');

    const refsLabel = slot.refs.map(r => r.label).join(', ') || '—';
    const statusIcon = uploading ? '<div class="spinner-sm"></div>' : (hasError ? '⚠️' : (resolved ? '✓' : (pending ? '?' : '📄')));
    // L'animation de « pop » n'est déclenchée que sur une photo tout
    // juste résolue avec succès (pas en cours d'envoi, pas en erreur) —
    // c'est le petit effet « wahou » de validation.
    const statusPopClass = (resolved && !uploading && !hasError) ? ' is-pop' : '';

    const file = pmSlotFile(slot);
    const sizeLabel = file
      ? `<div class="pm-slot-filesize">${escapeHtml(file.name)} — ${formatFileSize(file.size)}</div>`
      : '';

    // Le champ attend `slot.path` (ex. a.jpg), mais si l'extension du
    // fichier fourni diffère, le nom réellement utilisé est corrigé
    // (ex. a.png) — voir applyFileToSlot(). Sans ça, l'en-tête
    // affichait toujours le nom attendu même une fois la correction
    // acceptée, ce qui donnait l'impression trompeuse que rien n'avait
    // changé alors que le bon fichier était bien pris en compte.
    const effectiveFinalPath = slot.resolution ? slot.resolution.finalPath : (slot.pending ? slot.pending.finalPath : null);
    const showsRename = effectiveFinalPath && effectiveFinalPath !== slot.path;
    const pathLabel = showsRename
      ? `${escapeHtml(diffImgBase(slot.path))} <span class="pm-slot-rename-arrow">→</span> <span class="pm-slot-path-final">${escapeHtml(diffImgBase(effectiveFinalPath))}</span>`
      : escapeHtml(diffImgBase(slot.path));

    let inner = `
      <div class="pm-slot-visual">
        ${thumbUrl ? `<img class="pm-slot-thumb" src="${thumbUrl}" alt="" onerror="useImgFallback(this)">` : ''}
        <div class="pm-slot-status${statusPopClass}">${statusIcon}</div>
      </div>
      <div class="pm-slot-body">
        <div class="pm-slot-path">${pathLabel}</div>
        <div class="pm-slot-refs">Utilisée par : ${escapeHtml(refsLabel)}</div>
        ${sizeLabel}
    `;

    if (uploading){
      inner += `<div class="pm-slot-note is-warn">⏳ Envoi en cours vers le serveur…</div></div>`;
      card.innerHTML = inner;
      container.appendChild(card);
      return;
    }

    // Échec d'envoi : le fichier n'est PAS perdu — on garde la
    // sélection et on propose de réessayer juste cette photo, plutôt
    // que de forcer à tout recommencer depuis zéro.
    if (hasError){
      inner += `
        <div class="pm-slot-note is-error">❌ ${escapeHtml(slot.error)}</div>
        <div class="pm-slot-actions">
          <button type="button" class="btn btn-primary btn-sm" data-retry-upload="1">🔁 Réessayer l\u2019envoi</button>
          <button type="button" class="btn btn-outline btn-sm" data-clear-resolution="1">✕ Choisir un autre fichier</button>
        </div>
      `;
      inner += `</div>`;
      card.innerHTML = inner;
      const retryBtn = card.querySelector('[data-retry-upload]');
      if (retryBtn) retryBtn.addEventListener('click', () => {
        slot.error = null;
        if (PM_RETRY_HANDLER) PM_RETRY_HANDLER();
      });
      const clearErrBtn = card.querySelector('[data-clear-resolution]');
      if (clearErrBtn) clearErrBtn.addEventListener('click', () => {
        slot.error = null;
        slot.resolution = null;
        pmRevokeThumb(slot);
        renderPhotoSlots();
      });
      container.appendChild(card);
      return;
    }

    if (slot.suggestions && slot.suggestions.length && !resolved){
      inner += slot.suggestions.map(sugg => {
        const reasonText = sugg.reason === 'extension' ? 'même nom, extension différente'
          : sugg.reason === 'case' ? 'même nom, majuscules/minuscules différentes'
          : 'nom très proche — faute de frappe possible';
        return `
          <div class="pm-slot-note is-warn">💡 Une photo très proche existe déjà sur le serveur : <strong>${escapeHtml(diffImgBase(sugg.file))}</strong> (${reasonText}). Il s\u2019agit peut-être d\u2019une faute de frappe dans le champ plutôt que d\u2019une photo réellement manquante.</div>
          <div class="pm-slot-actions">
            <button type="button" class="btn btn-outline btn-sm" data-use-suggestion="${escapeHtml(sugg.file)}">✓ Utiliser ce fichier existant</button>
          </div>
        `;
      }).join('');
    }

    if (pending){
      const reasonText = slot.pending.reason === 'extension'
        ? `Ce fichier est un <strong>.${escapeHtml(slot.pending.extFile)}</strong> alors que le champ attend un <strong>.${escapeHtml(slot.pending.extSlot)}</strong>.`
        : slot.pending.reason === 'similar'
        ? `Le fichier « ${escapeHtml(slot.pending.file.name)} » ressemble au nom attendu — vérifie qu\u2019il s\u2019agit bien de la bonne photo avant de continuer.`
        : `Le fichier « ${escapeHtml(slot.pending.file.name)} » ressemble au nom attendu, mais son extension (.${escapeHtml(slot.pending.extFile)}) diffère de celle demandée (.${escapeHtml(slot.pending.extSlot)}).`;
      inner += `
        <div class="pm-slot-note is-warn">⚠️ ${reasonText}</div>
        <div class="pm-slot-actions">
          <button type="button" class="btn btn-primary btn-sm" data-confirm-pending="1">✓ Oui, utiliser cette photo</button>
          <button type="button" class="btn btn-outline btn-sm" data-reject-pending="1">✕ Choisir un autre fichier</button>
        </div>
      `;
    } else if (resolved){
      let label;
      if (slot.resolution.type === 'existing'){
        label = `Champ corrigé vers <strong>${escapeHtml(diffImgBase(slot.resolution.finalPath))}</strong> — cette photo est déjà en ligne, rien à envoyer.`;
      } else if (slot.uploaded){
        label = `Envoyée avec succès sous <strong>${escapeHtml(diffImgBase(slot.resolution.finalPath))}</strong>.`;
      } else {
        label = `Prête à être envoyée sous <strong>${escapeHtml(diffImgBase(slot.resolution.finalPath))}</strong>.`;
      }
      inner += `<div class="pm-slot-note is-ok">✓ ${label}</div>`;
      // Une fois réellement envoyée sur le serveur, on ne propose plus
      // de « retirer » — c'est déjà fait, revenir en arrière n'a plus
      // de sens à ce stade.
      if (!slot.uploaded){
        inner += `<div class="pm-slot-actions"><button type="button" class="btn btn-outline btn-sm" data-clear-resolution="1">↺ Retirer et recommencer</button></div>`;
      }
    } else {
      inner += `
        <div class="pm-slot-actions">
          <label class="pm-slot-file-label">📁 Choisir un fichier pour cette photo<input type="file" accept="${IMAGE_ACCEPT_ATTR}" data-manual-file style="display:none;"></label>
        </div>
      `;
    }

    inner += `</div>`;
    card.innerHTML = inner;

    card.querySelectorAll('[data-use-suggestion]').forEach(elBtn => {
      elBtn.addEventListener('click', () => {
        const sugg = slot.suggestions.find(s => s.file === elBtn.getAttribute('data-use-suggestion'));
        if (sugg) useSuggestion(slot, sugg);
      });
    });
    const confirmBtn = card.querySelector('[data-confirm-pending]');
    if (confirmBtn) confirmBtn.addEventListener('click', () => {
      slot.resolution = { type: 'upload', file: slot.pending.file, finalPath: slot.pending.finalPath };
      slot.pending = null;
      renderPhotoSlots();
    });
    const rejectBtn = card.querySelector('[data-reject-pending]');
    if (rejectBtn) rejectBtn.addEventListener('click', () => {
      PM_UNMATCHED.push(slot.pending.file);
      pmRevokeThumb(slot);
      slot.pending = null;
      renderPhotoSlots();
    });
    const clearBtn = card.querySelector('[data-clear-resolution]');
    if (clearBtn) clearBtn.addEventListener('click', () => {
      pmRevokeThumb(slot);
      slot.resolution = null;
      renderPhotoSlots();
    });
    const manualInput = card.querySelector('[data-manual-file]');
    if (manualInput) manualInput.addEventListener('change', () => {
      const f = manualInput.files[0];
      manualInput.value = '';
      if (!f) return;
      // Le filtre "accept" du sélecteur système est une simple suggestion
      // (l'utilisateur peut basculer sur "Tous les fichiers") : on revalide
      // donc ici aussi, comme pour tous les autres points d'entrée de photo.
      if (!isAcceptableImageFile(f)){
        showToast(IMAGE_ACCEPT_HINT, true);
        return;
      }
      const sizeError = imageSizeErrorMessage(f);
      if (sizeError){
        showToast(sizeError, true);
        return;
      }
      onManualFileChosen(slot, f);
    });

    container.appendChild(card);
  });

  renderUnmatchedPhotoFiles();

  const resolvedCount = PM_SLOTS.filter(s => !!s.resolution && !s.error).length;
  const erroredCount = PM_SLOTS.filter(s => !!s.error).length;
  $('#pmCount').textContent = resolvedCount + ' / ' + PM_SLOTS.length + ' photo(s) résolue(s)' +
    (erroredCount ? ' — ' + erroredCount + ' en erreur' : '') +
    (PM_UNMATCHED.length ? ' — ' + PM_UNMATCHED.length + ' fichier(s) non utilisé(s)' : '');
  $('#photosOkBtn').disabled = resolvedCount !== PM_SLOTS.length || erroredCount > 0;
}

const pmDropzone = $('#pmDropzone');
const pmDropInput = $('#pmDropInput');
pmDropzone.addEventListener('click', () => pmDropInput.click());
pmDropzone.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); pmDropInput.click(); } });
pmDropzone.addEventListener('dragover', (e) => { e.preventDefault(); pmDropzone.classList.add('is-drag'); });
pmDropzone.addEventListener('dragleave', () => pmDropzone.classList.remove('is-drag'));
pmDropzone.addEventListener('drop', (e) => {
  e.preventDefault();
  pmDropzone.classList.remove('is-drag');
  handleIncomingPhotoFiles(Array.from((e.dataTransfer && e.dataTransfer.files) || []));
});
pmDropInput.addEventListener('change', () => {
  handleIncomingPhotoFiles(Array.from(pmDropInput.files || []));
  pmDropInput.value = '';
});

/* Ouvre la modale de résolution des photos manquantes. Résout à
   `true` si toutes les photos ont été envoyées avec succès (ou
   résolues vers un fichier déjà existant) et que la publication peut
   continuer, `false` si l\u2019admin annule. */
function showMissingPhotosModal(missingItems, confirmPassword){
  return new Promise(resolve => {
    // Si la modale est rouverte (nouvelle tentative de publication),
    // on libère d'abord les aperçus de la fois précédente.
    PM_SLOTS.forEach(pmRevokeThumb);
    PM_SLOTS = missingItems.map(item => ({
      path: item.path, refs: item.refs, suggestions: item.suggestions || [],
      resolution: null, pending: null, uploading: false, uploaded: false, error: null,
    }));
    PM_UNMATCHED = [];

    $('#photosSubtitle').textContent = PM_SLOTS.length === 1
      ? "1 photo référencée dans le contenu n\u2019a pas été trouvée sur le serveur. Fournis-la (ou corrige le champ) avant de publier."
      : PM_SLOTS.length + " photos référencées dans le contenu n\u2019ont pas été trouvées sur le serveur. Fournis-les (ou corrige les champs) avant de publier.";
    renderPhotoSlots();

    const overlay = $('#photosOverlay');
    overlay.style.display = 'flex';
    requestAnimationFrame(() => overlay.classList.add('show'));

    const okBtn = $('#photosOkBtn');
    const cancelBtn = $('#photosCancelBtn');
    const originalOkLabel = okBtn.textContent;
    const progressWrap = $('#pmProgress');
    const progressFill = $('#pmProgressFill');
    const progressLabel = $('#pmProgressLabel');

    function setProgress(done, total, currentLabel){
      if (!total){ progressWrap.style.display = 'none'; return; }
      progressWrap.style.display = 'block';
      const pct = Math.round((done / total) * 100);
      progressFill.style.width = pct + '%';
      progressLabel.textContent = currentLabel
        ? `Envoi ${done + 1} / ${total} — ${currentLabel}`
        : `${done} / ${total} photo(s) envoyée(s)`;
    }
    function hideProgress(){ progressWrap.style.display = 'none'; progressFill.style.width = '0%'; }

    function cleanup(result){
      overlay.classList.remove('show');
      setTimeout(() => { overlay.style.display = 'none'; }, 300);
      okBtn.removeEventListener('click', onOk);
      cancelBtn.removeEventListener('click', onCancel);
      okBtn.textContent = originalOkLabel;
      hideProgress();
      PM_RETRY_HANDLER = null;
      if (!result) PM_SLOTS.forEach(pmRevokeThumb);
      resolve(result);
    }

    /* Envoie séquentiellement les photos pas encore envoyées (ignore
       celles déjà résolues « existing » et celles déjà envoyées avec
       succès — utile pour un réessai ciblé après une seule erreur,
       sans jamais renvoyer ce qui a déjà réussi). S'arrête à la
       première erreur en conservant le fichier en place : la carte
       correspondante passe en état « erreur » avec un bouton pour
       réessayer juste elle, plutôt que de tout faire recommencer. */
    async function onOk(){
      okBtn.disabled = true;
      cancelBtn.disabled = true;

      const toUpload = PM_SLOTS.filter(s => s.resolution && s.resolution.type === 'upload' && !s.uploaded);
      const totalUploads = PM_SLOTS.filter(s => s.resolution && s.resolution.type === 'upload').length;
      let doneUploads = totalUploads - toUpload.length;

      for (const slot of toUpload){
        const label = diffImgBase(slot.resolution.finalPath);
        okBtn.textContent = 'Envoi de ' + label + '…';
        setProgress(doneUploads, totalUploads, label);
        slot.uploading = true;
        renderPhotoSlots();
        const form = new FormData();
        form.append('photo', slot.resolution.file, slot.resolution.file.name);
        form.append('target_path', slot.resolution.finalPath);
        form.append('confirm_password', confirmPassword);
        try {
          // Timeout généreux (90s) : une photo peut être volumineuse et
          // la connexion de Morgane peut être lente (réseau mobile...).
          const res = await fetchWithTimeout('inc/upload-image.php', { method: 'POST', credentials: 'same-origin', body: form }, 90000);
          const data = await readJsonSafe(res);
          slot.uploading = false;
          if (res.status === 401){
            showToast('Session expirée — recharge la page et reconnecte-toi.', true);
            renderPhotoSlots();
            okBtn.disabled = false; cancelBtn.disabled = false; okBtn.textContent = originalOkLabel;
            hideProgress();
            return;
          }
          if (!res.ok || !data || !data.ok){
            slot.error = (data && data.message) || ('le serveur a répondu avec une erreur (' + res.status + ')');
            showToast('Échec de l\u2019envoi de « ' + label + ' » — corrige puis réessaie juste cette photo.', true);
            renderPhotoSlots();
            okBtn.disabled = false; cancelBtn.disabled = false; okBtn.textContent = originalOkLabel;
            hideProgress();
            return;
          }
        } catch(e){
          slot.uploading = false;
          slot.error = e.message || 'erreur réseau pendant l\u2019envoi.';
          showToast('Envoi de « ' + label + ' » interrompu — réessaie juste cette photo.', true);
          renderPhotoSlots();
          okBtn.disabled = false; cancelBtn.disabled = false; okBtn.textContent = originalOkLabel;
          hideProgress();
          return;
        }
        slot.uploaded = true;
        doneUploads++;
        setProgress(doneUploads, totalUploads, null);
        renderPhotoSlots();
      }

      // Toutes les photos nécessaires ont été envoyées (ou étaient déjà
      // en ligne) : on applique maintenant les chemins définitifs à
      // CONTENT, en une seule fois, pour ne rien laisser d'incohérent
      // si une étape précédente avait échoué.
      PM_SLOTS.forEach(slot => {
        slot.refs.forEach(r => r.set(slot.resolution.finalPath));
      });

      cleanup(true);
    }
    function onCancel(){ cleanup(false); }
    PM_RETRY_HANDLER = onOk;
    okBtn.disabled = false;
    cancelBtn.disabled = false;
    okBtn.addEventListener('click', onOk);
    cancelBtn.addEventListener('click', onCancel);
  });
}

/* =====================================================================
   MOTEUR DE COMPARAISON (diff) ENTRE DEUX CONTENUS
   Produit une liste de sections lisibles ("Cours & tarifs", "Page
   d'accueil"…) avec, pour chacune, les changements ajout / suppression
   / modification en français plutôt qu'un diff JSON brut.
   ===================================================================== */
const DIFF_SECTION_LABELS = {
  hero: "Page d'accueil",
  presentation: 'Présentation',
  pourquoi: 'Section « Pourquoi »',
  tags: 'Étiquettes',
  services: 'Cours & tarifs',
  images: 'Images principales',
  galerie: 'Galerie photo',
  galerieTexte: 'Galerie photo (titre et affichage)',
  nav: 'Menu de navigation',
  theme: 'Couleurs du site',
  footer: 'Pied de page',
  seo: 'SEO',
  contact: 'Coordonnées de contact',
  visibility: 'Visibilité du site',
};

const DIFF_SECTION_ICONS = {
  hero: '🏠', presentation: '👋', pourquoi: '💡', tags: '🏷️', services: '🐾',
  images: '🖼️', galerie: '📷', galerieTexte: '📷', nav: '🧭', theme: '🎨', footer: '📄',
  seo: '🔍', contact: '📇', visibility: '👁️',
  'cleanup-photos': '🗑️', 'cleanup-backups': '🗂',
};

const DIFF_FIELD_LABELS = {
  badge: 'Badge', titre: 'Titre', texte: 'Texte', texte1: 'Premier paragraphe',
  texte2: 'Second paragraphe', eyebrow: 'Eyebrow', citation: 'Citation',
  email: 'Email', telephone: 'Téléphone', zone: "Zone d'intervention",
  ville: 'Ville', facebook: 'Facebook', instagram: 'Instagram',
  categorie: 'Catégorie', image: 'Image', description: 'Description',
  prix: 'Prix', unite: 'Unité', visible: 'Affichage',
  cadrage: 'Cadrage (mobile)', cadrageDesktop: 'Cadrage (grand écran)',
  copyright: 'Copyright', tagline: 'Signature',
  siteTitle: 'Titre du site', siteUrl: 'Adresse du site', ogType: 'Type OGType',
  shareImage: 'Image de partage', label: 'Texte du lien', id: 'Identifiant',
  layout: "Type d'affichage",
  hero: 'Photo d\u2019accueil', contact: 'Fond de la section contact', portrait: 'Portrait',
  ink: 'Texte', paper: 'Fond', paperSoft: 'Fond secondaire', pineDark: 'Accent foncé',
  pine: 'Accent', olive: 'Olive', gold: 'Doré', goldLight: 'Doré clair', cream: 'Crème',
};

const DIFF_VISIBILITY_LABELS = {
  presentation: 'Présentation', pourquoi: 'Section « Pourquoi »', tags: 'Étiquettes',
  galerie: 'Galerie photo', map: 'Carte (Google Maps)', facebook: 'Lien Facebook',
  instagram: 'Lien Instagram',
};

function diffFieldLabel(key){ return DIFF_FIELD_LABELS[key] || key; }
/* N'a plus jamais tronqué le texte depuis le retour d'expérience de
   l'utilisatrice : un comparatif incomplet est pire qu'un comparatif un
   peu long. Ne fait plus que la mise en forme "(vide)" pour un champ vide. */
function diffTruncate(v){
  if (v === null || v === undefined) return '(vide)';
  const s = String(v);
  return s === '' ? '(vide)' : s;
}
function isColorField(key){
  return ['ink','paper','paperSoft','pineDark','pine','olive','gold','goldLight','cream'].includes(key);
}
function diffSwatch(hex){
  if (!/^#[0-9a-fA-F]{3,8}$/.test(String(hex))) return '';
  return `<span class="diff-swatch" style="background:${escapeHtml(hex)};"></span>`;
}
/* =====================================================================
   DIFF MOT-À-MOT — pour les champs de texte (titres, paragraphes,
   descriptions...) : au lieu d'afficher deux blocs bruts "avant"/"après"
   à comparer soi-même, on montre le texte final avec les mots retirés
   barrés en rouge et les mots ajoutés soulignés en vert, comme un
   correcteur de document. Beaucoup plus rapide à lire pour une simple
   faute corrigée, un mot en plus/en moins, ou une phrase ajoutée/retirée
   — le cas le plus courant en pratique.
   Algorithme : plus longue sous-séquence commune (LCS) au niveau des
   mots, calculée en programmation dynamique. Volontairement borné (voir
   WORD_DIFF_MAX_TOKENS) : au-delà, la matrice deviendrait trop grande
   pour rester instantanée — dans ce cas (rarissime en pratique pour des
   textes de site vitrine) on retombe simplement sur l'ancien affichage
   avant/après en deux blocs, jamais tronqué non plus.
   ===================================================================== */
const WORD_DIFF_MAX_TOKENS = 700;
function tokenizeForWordDiff(str){
  // Découpe en mots tout en conservant séparément les espaces/retours à
  // la ligne, pour que le texte recollé (equal + ins) reste identique au
  // texte d'origine caractère pour caractère, espaces comprises.
  return (str === null || str === undefined ? '' : String(str)).match(/\s+|[^\s]+/g) || [];
}
function computeWordDiffOps(oldStr, newStr){
  const a = tokenizeForWordDiff(oldStr);
  const b = tokenizeForWordDiff(newStr);
  const n = a.length, m = b.length;
  if (n > WORD_DIFF_MAX_TOKENS || m > WORD_DIFF_MAX_TOKENS) return null;
  // Table LCS : dp[i][j] = longueur de la plus longue sous-séquence
  // commune entre a[i..] et b[j..].
  const dp = new Array(n + 1);
  for (let i = 0; i <= n; i++) dp[i] = new Uint32Array(m + 1);
  for (let i = n - 1; i >= 0; i--){
    for (let j = m - 1; j >= 0; j--){
      dp[i][j] = a[i] === b[j] ? dp[i+1][j+1] + 1 : Math.max(dp[i+1][j], dp[i][j+1]);
    }
  }
  const ops = [];
  let i = 0, j = 0;
  while (i < n && j < m){
    if (a[i] === b[j]){ ops.push({ type:'equal', text:a[i] }); i++; j++; }
    else if (dp[i+1][j] >= dp[i][j+1]){ ops.push({ type:'del', text:a[i] }); i++; }
    else { ops.push({ type:'ins', text:b[j] }); j++; }
  }
  while (i < n){ ops.push({ type:'del', text:a[i] }); i++; }
  while (j < m){ ops.push({ type:'ins', text:b[j] }); j++; }
  return ops;
}
/* Rend le HTML du diff mot-à-mot, ou `null` si le texte dépasse la
   limite (voir ci-dessus) — l'appelant doit alors utiliser un affichage
   de secours (avant/après classique, jamais tronqué non plus). */
function wordDiffHtml(oldStr, newStr){
  const ops = computeWordDiffOps(oldStr, newStr);
  if (!ops) return null;
  let out = '';
  ops.forEach(op => {
    if (op.type === 'equal') out += escapeHtml(op.text);
    else if (op.type === 'del') out += `<del class="diff-word-del">${escapeHtml(op.text)}</del>`;
    else out += `<ins class="diff-word-ins">${escapeHtml(op.text)}</ins>`;
  });
  return out || '<span class="diff-word-empty">(vide)</span>';
}
/* Retire le préfixe de dossier ("images/" ou "images/galerie/") pour
   n'afficher que le nom de fichier dans les messages de comparaison. */
function diffImgBase(path){
  const v = (path || '').trim();
  return v.replace(/^images\/(galerie\/)?/, '');
}

/* Rendu HTML d'une comparaison photo « avant / après », utilisée pour
   tout champ image (images principales, prestations, remplacement dans
   la galerie). `oldSrc`/`newSrc` vides => emplacement vide affiché en
   pointillés plutôt qu'un cadre cassé. Quand les deux sont renseignées
   et diffèrent, les src sont posées en data-attributes pour permettre
   à checkPhotoRenames() de vérifier après coup (async, côté image) si
   c'est vraiment une autre photo ou juste un renommage du fichier — le
   texte/tag est alors ajusté sans que la ligne ait à être recalculée. */
function diffImgCompareHtml({ oldSrc, newSrc, label, replaceTag }){
  const box = (src, cls, tag) => src
    ? `<div class="diff-img-box ${cls}"><img src="${escapeHtml(src)}" alt="" loading="lazy" onerror="this.parentElement.classList.add('is-broken')"><span class="diff-img-tag">${tag}</span><span class="diff-img-name">${escapeHtml(diffImgBase(src))}</span></div>`
    : `<div class="diff-img-box is-empty"><span class="diff-img-empty-icon">🚫</span><span class="diff-img-tag">${tag}</span></div>`;
  const canBeRename = oldSrc && newSrc && oldSrc !== newSrc;
  const renameAttrs = canBeRename ? ` data-old-src="${escapeHtml(oldSrc)}" data-new-src="${escapeHtml(newSrc)}"` : '';
  return `<div class="diff-photo-row"${renameAttrs}>`
    + (label ? `<p class="diff-photo-label">${label}${replaceTag ? '<span class="diff-photo-replace-tag">🔁 Remplacée</span>' : ''}</p>` : '')
    + `<div class="diff-img-compare">${box(oldSrc, 'is-old', 'Avant')}<span class="diff-img-arrow">→</span>${box(newSrc, 'is-new', 'Après')}</div></div>`;
}

/* Comme parsePos() dans le sélecteur de point focal (voir plus haut) :
   convertit "62% 20%" en {x,y}, ou le centre {50,50} si vide/absent. */
function diffCadragePos(v){
  const m = /^(-?\d+(?:\.\d+)?)%\s+(-?\d+(?:\.\d+)?)%$/.exec((v || '').trim());
  if (!m) return { x:50, y:50 };
  return { x: Math.min(100, Math.max(0, parseFloat(m[1]))), y: Math.min(100, Math.max(0, parseFloat(m[2]))) };
}

/* Rendu HTML de la mini-comparaison visuelle d'un changement de CADRAGE
   (point focal) — pas de la photo elle-même. Plutôt que deux
   pourcentages bruts à interpréter mentalement, une carte-repère très
   petite situe les deux points (avant en rouge, après en doré) sur la
   photo entière, et deux minuscules aperçus « avant/après » montrent le
   recadrage réel obtenu, au ratio exact du format concerné — cohérent
   visuellement avec le sélecteur de cadrage lui-même (mêmes ratios 8/5
   et 31/60). `imageUrl` est la photo actuelle du cours (nécessairement
   fournie par l'appelant) ; sans elle l'appelant doit utiliser l'ancien
   texte brut en repli, voir diffServices(). */
/* Repositionne une coordonnée 0-100% vers une plage resserrée : le point
   de cadrage est un disque de 10px affiché dans une carte de 72px (58px
   sur mobile). Sans ce resserrement, un point posé pile à 0% ou 100%
   place son centre exactement sur le bord de la carte : la moitié du
   disque sort du cadre et se fait rogner par l'overflow:hidden, ce qui
   ressemble à un pastille à moitié effacée plutôt qu'à un point posé au
   bord de la photo. Ne s'applique qu'au marqueur affiché sur la
   carte-repère — jamais au recadrage réel (background-position des deux
   aperçus « avant/après »), qui doit lui rester fidèle à 0-100%. */
function diffCadrageDotPos(v){
  const INSET = 8; // % de marge de sécurité de chaque côté
  return INSET + (Math.min(100, Math.max(0, v)) / 100) * (100 - INSET * 2);
}

/* Compteur servant à donner un id unique à chaque bloc de comparaison de
   cadrage affiché simultanément (plusieurs cours modifiés à la fois dans
   la même fenêtre de comparaison) — utilisé uniquement pour le message
   de diagnostic si besoin, la détection d'échec elle-même se fait via
   closest() donc sans avoir besoin de cet id. */
let diffCadrageUidSeq = 0;

function diffCadrageCompareHtml({ imageUrl, oldVal, newVal, variant, label }){
  const op = diffCadragePos(oldVal), np = diffCadragePos(newVal);
  const url = (imageUrl || '').trim();
  const bg = `url("${escapeHtml(url)}")`;
  const cropCls = variant === 'desktop' ? 'is-desktop' : 'is-mobile';
  const crop = (pos, kind, tag) => `<div class="diff-cadrage-crop ${cropCls}" style="background-image:${bg};background-position:${pos.x}% ${pos.y}%;"><span class="diff-cadrage-crop-tag ${kind}">${tag}</span></div>`;
  diffCadrageUidSeq++;
  // Note de repli textuelle : si la photo est introuvable (fichier
  // supprimé/renommé sur le serveur), on ne perd pas l'information utile
  // (les positions avant/après restent lisibles en pourcentages) plutôt
  // que d'afficher un bloc vide et silencieux.
  const brokenNote = `<span class="diff-cadrage-broken-note">🖼️ Photo introuvable — cadrage : `
    + `${Math.round(op.x)}% / ${Math.round(op.y)}%<span class="diff-cadrage-crop-arrow">→</span>${Math.round(np.x)}% / ${Math.round(np.y)}%</span>`;
  // `imageUrl` manquante (cas limite, ne devrait pas arriver vu le garde-
  // fou dans diffServices()) : on bascule directement en repli plutôt que
  // de poser un background-image vide qui se fondrait dans le fond.
  if (!url){
    return `<div class="diff-photo-row">`
      + (label ? `<p class="diff-photo-label">${label}</p>` : '')
      + `<div class="diff-cadrage-compare is-broken">${brokenNote}</div></div>`;
  }
  return `<div class="diff-photo-row">`
    + (label ? `<p class="diff-photo-label">${label}</p>` : '')
    + `<div class="diff-cadrage-compare">`
      + `<img class="diff-cadrage-probe" src="${escapeHtml(url)}" alt="" aria-hidden="true" onerror="this.parentElement.classList.add('is-broken')">`
      + `<div class="diff-cadrage-map" style="background-image:${bg};" title="Position du point de cadrage sur la photo entière">`
        + `<span class="diff-cadrage-dot is-old" style="left:${diffCadrageDotPos(op.x)}%;top:${diffCadrageDotPos(op.y)}%;" title="Avant : ${Math.round(op.x)}% / ${Math.round(op.y)}%"></span>`
        + `<span class="diff-cadrage-dot is-new" style="left:${diffCadrageDotPos(np.x)}%;top:${diffCadrageDotPos(np.y)}%;" title="Après : ${Math.round(np.x)}% / ${Math.round(np.y)}%"></span>`
      + `</div>`
      + `<div class="diff-cadrage-crops">${crop(op,'is-old','Avant')}<span class="diff-cadrage-crop-arrow">→</span>${crop(np,'is-new','Après')}</div>`
      + brokenNote
    + `</div></div>`;
}

/* Charge une image et résout avec l'élément une fois décodée (rejette
   sur erreur — fichier manquant, pas encore déployé sur le serveur…). */
function loadImageEl(src){
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = () => resolve(img);
    img.onerror = reject;
    img.src = src;
  });
}

/* Compare deux images par leur contenu (pas par leur nom de fichier) :
   sert à distinguer un vrai remplacement de photo d'un simple
   renommage du fichier (même photo, nom différent). Comparaison sur
   une miniature réduite (rapide, fiable pour une image strictement
   identique) plutôt que sur la pleine résolution. Retourne false —
   jamais une fausse certitude — dès que la comparaison est impossible
   (image manquante, pas encore déployée, ou canvas inaccessible). */
async function imagesLookIdentical(srcA, srcB){
  if (!srcA || !srcB) return false;
  if (srcA === srcB) return true;
  try {
    const [imgA, imgB] = await Promise.all([loadImageEl(srcA), loadImageEl(srcB)]);
    if (!imgA.naturalWidth || !imgB.naturalWidth) return false;
    if (imgA.naturalWidth !== imgB.naturalWidth || imgA.naturalHeight !== imgB.naturalHeight) return false;
    const w = Math.min(imgA.naturalWidth, 96);
    const h = Math.max(1, Math.round(w * imgA.naturalHeight / imgA.naturalWidth));
    const drawOn = (img) => {
      const c = document.createElement('canvas');
      c.width = w; c.height = h;
      c.getContext('2d').drawImage(img, 0, 0, w, h);
      return c.getContext('2d').getImageData(0, 0, w, h).data;
    };
    const dataA = drawOn(imgA), dataB = drawOn(imgB);
    if (dataA.length !== dataB.length) return false;
    for (let i = 0; i < dataA.length; i++){
      if (dataA[i] !== dataB[i]) return false;
    }
    return true;
  } catch(e){
    return false;
  }
}

/* Repasse sur les comparaisons photo déjà affichées dans la modale de
   diff et, pour celles où l'image a réellement changé de nom, vérifie
   en tâche de fond si c'est la même photo (contenu identique) ou une
   photo différente. Si c'est la même, remplace le tag « Remplacée »
   par « Renommée » et ajoute une note explicite — sans bloquer
   l'ouverture de la modale, qui reste immédiate. */
function checkPhotoRenames(scopeEl){
  $$('.diff-photo-row[data-old-src][data-new-src]', scopeEl).forEach((rowEl) => {
    if (rowEl.dataset.photoChecked) return;
    rowEl.dataset.photoChecked = '1';
    const oldSrc = rowEl.dataset.oldSrc, newSrc = rowEl.dataset.newSrc;
    imagesLookIdentical(oldSrc, newSrc).then(same => {
      if (!same || !rowEl.isConnected) return;
      rowEl.classList.add('is-samephoto');
      const tag = rowEl.querySelector('.diff-photo-replace-tag');
      if (tag) tag.textContent = '📝 Renommée';
      rowEl.insertAdjacentHTML('beforeend', `<p class="diff-photo-samenote">🟰 Photo identique — seul le nom du fichier a changé (<code>${escapeHtml(diffImgBase(oldSrc))}</code> → <code>${escapeHtml(diffImgBase(newSrc))}</code>), l'image affichée sur le site ne change pas visuellement.</p>`);
    });
  });
}

/* Compare deux objets "plats" (champs simples) et retourne une liste de
   lignes {type, html, label, revert}. `keys` limite/priorise les champs
   à comparer ; si omis, l'union des clés des deux objets est utilisée.
   `opts.section` porte le nom de section utilisé pour l'annulation
   ciblée d'une ligne ; `opts.imageFields` liste les champs à afficher
   en comparaison photo plutôt qu'en texte. */
function diffFlatObject(oldObj, newObj, keys, opts){
  oldObj = oldObj || {}; newObj = newObj || {};
  opts = opts || {};
  const section = opts.section || null;
  const imageFields = opts.imageFields || null;
  const rows = [];
  const list = keys || Array.from(new Set([...Object.keys(oldObj), ...Object.keys(newObj)]));
  for (const key of list){
    let ov = oldObj[key], nv = newObj[key];
    // Normalise les valeurs "absentes" avant de comparer : un champ qui
    // n'existe pas encore dans le contenu publié (ex. `layout`, ajouté
    // après coup) et sa valeur par défaut affichée sont sémantiquement
    // identiques — sans cette normalisation, revenir à la valeur par
    // défaut après l'avoir changée puis rétablie fait apparaître un faux
    // changement (ex. "Grille → Grille" alors que rien n'a bougé). Les
    // champs texte "vides" suivent la même logique : `undefined`/`null`
    // valent `''`, exactement comme diffTruncate() les affiche déjà tous
    // les deux comme "(vide)".
    if (key === 'layout'){
      if (ov === undefined || ov === null || ov === '') ov = 'grid';
      if (nv === undefined || nv === null || nv === '') nv = 'grid';
    } else if (typeof ov !== 'object' && typeof nv !== 'object'){
      if (ov === undefined || ov === null) ov = '';
      if (nv === undefined || nv === null) nv = '';
    }
    if (JSON.stringify(ov) === JSON.stringify(nv)) continue;
    const label = diffFieldLabel(key);
    const revert = { kind:'field', section, field:key };
    if (imageFields && imageFields.includes(key)){
      rows.push({
        type:'modified', variant:'photo',
        html: diffImgCompareHtml({ oldSrc: ov || '', newSrc: nv || '', label: `<strong>${escapeHtml(label)}</strong>` }),
        label: `${label} : ${diffImgBase(ov)} → ${diffImgBase(nv)}`,
        field:key, revert
      });
    } else if (isColorField(key) && typeof ov === 'string' && typeof nv === 'string'){
      rows.push({ type:'modified', html: `<strong>${escapeHtml(label)}</strong> : ${diffSwatch(ov)}<span class="diff-old">${escapeHtml(ov)}</span><span class="diff-arrow">→</span>${diffSwatch(nv)}<span class="diff-new">${escapeHtml(nv)}</span>`, label: `${label} : ${ov} → ${nv}`, field:key, revert });
    } else if (key === 'visible' || typeof ov === 'boolean' || typeof nv === 'boolean'){
      const fmt = v => v ? 'affiché·e' : 'masqué·e';
      rows.push({ type:'modified', html: `<strong>${escapeHtml(label)}</strong> : <span class="diff-old">${fmt(ov)}</span><span class="diff-arrow">→</span><span class="diff-new">${fmt(nv)}</span>`, label: `${label} : ${fmt(ov)} → ${fmt(nv)}`, field:key, revert });
    } else if (key === 'layout' && GALLERY_LAYOUT_OPTIONS){
      const fmt = v => (GALLERY_LAYOUT_OPTIONS.find(o => o.key === v) || {}).label || (v || 'Grille');
      rows.push({ type:'modified', html: `<strong>${escapeHtml(label)}</strong> : <span class="diff-old">${escapeHtml(fmt(ov))}</span><span class="diff-arrow">→</span><span class="diff-new">${escapeHtml(fmt(nv))}</span>`, label: `${label} : ${fmt(ov)} → ${fmt(nv)}`, field:key, revert });
    } else {
      const wd = wordDiffHtml(ov, nv);
      const compareHtml = wd
        ? `<div class="diff-word-block">${wd}</div>`
        : `<span class="diff-old">${escapeHtml(diffTruncate(ov))}</span><span class="diff-arrow">→</span><span class="diff-new">${escapeHtml(diffTruncate(nv))}</span>`;
      rows.push({ type:'modified', html: `<strong>${escapeHtml(label)}</strong> :<br>${compareHtml}`, label: `${label} : ${diffTruncate(ov)} → ${diffTruncate(nv)}`, field:key, revert });
    }
  }
  return rows;
}

/* Compare deux tableaux de chaînes (étiquettes…) : ajouts et
   suppressions, sans tenir compte de l'ordre. `section` sert à cibler
   l'annulation d'une ligne précise (ex. « tags »). */
function diffStringArray(oldArr, newArr, section){
  oldArr = oldArr || []; newArr = newArr || [];
  const rows = [];
  const added = newArr.filter(v => !oldArr.includes(v));
  const removed = oldArr.filter(v => !newArr.includes(v));
  added.forEach(v => rows.push({ type:'added', html: `Ajout : <strong>${escapeHtml(diffTruncate(v))}</strong>`, label: `Ajout : ${diffTruncate(v)}`, revert: { kind:'stringArray', section, action:'remove', value:v } }));
  removed.forEach(v => rows.push({ type:'removed', html: `Suppression : <strong>${escapeHtml(diffTruncate(v))}</strong>`, label: `Suppression : ${diffTruncate(v)}`, revert: { kind:'stringArray', section, action:'restore', value:v } }));

  /* Réordonnancement pur (mêmes éléments des deux côtés, juste déplacés) :
     sans ce bloc, un simple glisser-déposer resterait invisible ici — et
     donc aussi invisible pour la pastille "modifications non téléchargées"
     et pour la publication (qui se croirait à jour et n'enverrait rien). */
  const common = newArr.filter(v => oldArr.includes(v));
  if (common.length >= 2){
    const oldOrder = oldArr.filter(v => newArr.includes(v));
    if (oldOrder.join('\u0001') !== common.join('\u0001')){
      const label = (DIFF_SECTION_LABELS[section] || 'Éléments').toLowerCase();
      rows.push({
        type:'modified',
        html: `Ordre des ${escapeHtml(label)} modifié (${common.length} éléments réorganisés)`,
        label: `Réorganisation : ${label} (${common.length})`,
        revert: { kind:'stringArrayReorder', section }
      });
    }
  }
  return rows;
}

/* Compare deux tableaux de cours/prestations (identifiés par leur
   titre). Un cours dont le titre change apparaît comme une suppression
   + un ajout — c'est volontairement simple et sans ambiguïté. */
function diffServices(oldArr, newArr){
  oldArr = oldArr || []; newArr = newArr || [];
  const rows = [];
  const oldByTitle = new Map(oldArr.map(s => [s.titre, s]));
  const usedTitles = new Set();
  newArr.forEach(ns => {
    const os = oldByTitle.get(ns.titre);
    if (os){
      usedTitles.add(ns.titre);
      const fieldRows = diffFlatObject(os, ns, ['categorie','image','description','prix','unite','cadrage','cadrageDesktop','visible'], { imageFields:['image'] });
      fieldRows.forEach(r => {
        const titleTag = `<strong>${escapeHtml(diffTruncate(ns.titre, 40))}</strong>`;
        // Changement de CADRAGE (point focal) : mini-comparaison visuelle
        // sur la photo actuelle plutôt que deux pourcentages bruts — voir
        // diffCadrageCompareHtml(). Repli sur le texte brut si la photo
        // n'est pas connue des deux côtés (cas limite très rare).
        const isCadrage = r.field === 'cadrage' || r.field === 'cadrageDesktop';
        const cadrageImg = isCadrage ? (ns.image || os.image || '').trim() : '';
        let html, variant = r.variant;
        if (r.variant === 'photo'){
          html = diffImgCompareHtml({ oldSrc: os.image || '', newSrc: ns.image || '', label: `${titleTag} — Photo` });
        } else if (isCadrage && cadrageImg){
          html = diffCadrageCompareHtml({
            imageUrl: cadrageImg, oldVal: os[r.field] || '', newVal: ns[r.field] || '',
            variant: r.field === 'cadrageDesktop' ? 'desktop' : 'mobile',
            label: `${titleTag} — ${diffFieldLabel(r.field)}`
          });
          variant = 'cadrage';
        } else {
          html = `${titleTag} — ${r.html.replace(/^<strong>.*?<\/strong>\s*:\s*(<br>)?/, '')}`;
        }
        rows.push({
          type:'modified', variant,
          html,
          label: `${diffTruncate(ns.titre, 40)} — ${r.label.replace(/^.*?: /, '')}`,
          revert: { kind:'serviceField', titre: ns.titre, field: r.field }
        });
      });
    } else {
      rows.push({ type:'added', html: `Nouveau cours : <strong>${escapeHtml(diffTruncate(ns.titre, 50))}</strong>`, label: `Nouveau cours : ${diffTruncate(ns.titre, 50)}`, revert: { kind:'serviceRemove', titre: ns.titre } });
    }
  });
  oldArr.forEach(os => {
    if (!usedTitles.has(os.titre)) rows.push({ type:'removed', html: `Cours supprimé : <strong>${escapeHtml(diffTruncate(os.titre, 50))}</strong>`, label: `Cours supprimé : ${diffTruncate(os.titre, 50)}`, revert: { kind:'serviceRestore', titre: os.titre } });
  });

  /* Réordonnancement pur des cours (mêmes titres des deux côtés) — même
     remarque que pour les étiquettes et le menu : sans ce bloc, dupliquer
     ou réorganiser les cartes de cours sans autre modification resterait
     invisible, y compris pour la publication. */
  if (usedTitles.size >= 2){
    const oldOrder = oldArr.filter(s => usedTitles.has(s.titre)).map(s => s.titre);
    const newOrder = newArr.filter(s => usedTitles.has(s.titre)).map(s => s.titre);
    if (oldOrder.join('\u0001') !== newOrder.join('\u0001')){
      rows.push({
        type:'modified',
        html: `Ordre des cours modifié (${usedTitles.size} cours réorganisés)`,
        label: `Réorganisation des cours (${usedTitles.size})`,
        revert: { kind:'serviceReorder' }
      });
    }
  }
  return rows;
}

/* Compare deux galeries (tableaux de {src, alt}), identifiées par src.
   Un premier passage détecte les remplacements « au même emplacement »
   (même index, ancien src et nouveau src introuvables ailleurs dans
   l'autre tableau) pour les fusionner en une seule ligne avant/après
   plutôt que deux lignes séparées ajout + suppression. Un dernier
   passage compare l'ordre relatif des photos présentes des deux côtés
   (donc ni ajoutées, ni supprimées, ni remplacées) : si cet ordre a
   changé, une ligne dédiée « galerie réorganisée » est ajoutée, sinon
   un simple déplacement resterait invisible dans le diff. */
function diffGalerie(oldArr, newArr){
  oldArr = oldArr || []; newArr = newArr || [];
  const rows = [];
  const oldBySrc = new Map(oldArr.filter(g => g && g.src).map(g => [g.src, g]));
  const newBySrc = new Map(newArr.filter(g => g && g.src).map(g => [g.src, g]));
  const usedSrc = new Set();
  const replacedIdx = new Set();

  const maxLen = Math.max(oldArr.length, newArr.length);
  for (let i = 0; i < maxLen; i++){
    const og = oldArr[i], ng = newArr[i];
    if (og && og.src && ng && ng.src && og.src !== ng.src && !newBySrc.has(og.src) && !oldBySrc.has(ng.src)){
      rows.push({
        type:'modified', variant:'photo',
        html: diffImgCompareHtml({ oldSrc: og.src, newSrc: ng.src, label: `Photo ${i + 1} de la galerie`, replaceTag:true }),
        label: `Photo remplacée (emplacement ${i + 1}) : ${diffImgBase(og.src)} → ${diffImgBase(ng.src)}`,
        revert: { kind:'galerieReplace', index:i }
      });
      usedSrc.add(og.src);
      replacedIdx.add(i);
    }
  }

  newArr.forEach((ng, i) => {
    if (replacedIdx.has(i)) return;
    if (!ng || !ng.src){ rows.push({ type:'added', html: `Nouvelle photo (emplacement ${i+1}) — <span class="diff-old">nom de fichier non renseigné</span>`, label: `Nouvelle photo (emplacement ${i+1}) sans nom de fichier` }); return; }
    const og = oldBySrc.get(ng.src);
    if (og){
      usedSrc.add(ng.src);
      if ((og.alt || '') !== (ng.alt || '')){
        const wd = wordDiffHtml(og.alt, ng.alt);
        const compareHtml = wd
          ? `<div class="diff-word-block">${wd}</div>`
          : `<span class="diff-old">${escapeHtml(diffTruncate(og.alt))}</span><span class="diff-arrow">→</span><span class="diff-new">${escapeHtml(diffTruncate(ng.alt))}</span>`;
        rows.push({ type:'modified', html: `Photo <strong>${escapeHtml(diffImgBase(ng.src))}</strong> — texte alternatif :<br>${compareHtml}`, label: `Texte alternatif de ${diffImgBase(ng.src)} : ${diffTruncate(og.alt)} → ${diffTruncate(ng.alt)}`, revert: { kind:'galerieAlt', src: ng.src } });
      }
    } else {
      rows.push({ type:'added', html: `Nouvelle photo ajoutée à la galerie : <strong>${escapeHtml(diffImgBase(ng.src))}</strong>`, label: `Nouvelle photo : ${diffImgBase(ng.src)}`, revert: { kind:'galerieRemove', src: ng.src } });
    }
  });
  oldArr.forEach(og => {
    if (og && og.src && !usedSrc.has(og.src)) rows.push({ type:'removed', html: `Photo retirée de la galerie : <strong>${escapeHtml(diffImgBase(og.src))}</strong>`, label: `Photo retirée : ${diffImgBase(og.src)}`, revert: { kind:'galerieRestore', src: og.src } });
  });

  /* Photos « communes » aux deux versions : matchées par src dans la
     boucle ci-dessus (usedSrc en contient le src, identique des deux
     côtés), à l'exclusion des remplacements en place (ceux-là ont
     ajouté l'ANCIEN src à usedSrc, un src qui n'existe plus côté
     nouveau — newBySrc.has(...) les élimine donc naturellement). */
  const commonSrc = new Set([...usedSrc].filter(src => newBySrc.has(src) && oldBySrc.has(src)));
  if (commonSrc.size >= 2){
    const oldOrder = oldArr.filter(g => g && g.src && commonSrc.has(g.src)).map(g => g.src);
    const newOrder = newArr.filter(g => g && g.src && commonSrc.has(g.src)).map(g => g.src);
    if (oldOrder.join('\u0001') !== newOrder.join('\u0001')){
      rows.push({
        type:'modified',
        html: `Galerie réorganisée : l'ordre de ${commonSrc.size} photo${commonSrc.size > 1 ? 's' : ''} existante${commonSrc.size > 1 ? 's' : ''} a changé`,
        label: `Galerie réorganisée (${commonSrc.size} photos)`,
        revert: { kind:'galerieReorder' }
      });
    }
  }
  return rows;
}

function diffNav(oldArr, newArr){
  oldArr = oldArr || []; newArr = newArr || [];
  const rows = [];
  const oldById = new Map(oldArr.map(n => [n.id, n]));
  const usedIds = new Set();
  newArr.forEach(nn => {
    const on = oldById.get(nn.id);
    if (on){
      usedIds.add(nn.id);
      if (on.label !== nn.label) rows.push({ type:'modified', html: `Lien menu : <span class="diff-old">${escapeHtml(on.label)}</span><span class="diff-arrow">→</span><span class="diff-new">${escapeHtml(nn.label)}</span>`, label: `Lien menu : ${on.label} → ${nn.label}`, revert: { kind:'navField', id: nn.id, field:'label' } });
    } else {
      rows.push({ type:'added', html: `Nouveau lien de menu : <strong>${escapeHtml(nn.label)}</strong>`, label: `Nouveau lien de menu : ${nn.label}`, revert: { kind:'navRemove', id: nn.id } });
    }
  });
  oldArr.forEach(on => { if (!usedIds.has(on.id)) rows.push({ type:'removed', html: `Lien de menu supprimé : <strong>${escapeHtml(on.label)}</strong>`, label: `Lien de menu supprimé : ${on.label}`, revert: { kind:'navRestore', id: on.id } }); });

  /* Réordonnancement pur des liens (mêmes id des deux côtés) — voir la
     même remarque que dans diffStringArray : sans ce bloc, réorganiser
     le menu sans autre changement passerait inaperçu, y compris lors
     de la publication. */
  const commonIds = new Set([...usedIds].filter(id => oldById.has(id)));
  if (commonIds.size >= 2){
    const oldOrder = oldArr.filter(n => commonIds.has(n.id)).map(n => n.id);
    const newOrder = newArr.filter(n => commonIds.has(n.id)).map(n => n.id);
    if (oldOrder.join('\u0001') !== newOrder.join('\u0001')){
      rows.push({
        type:'modified',
        html: `Ordre du menu modifié (${commonIds.size} liens réorganisés)`,
        label: `Réorganisation du menu (${commonIds.size})`,
        revert: { kind:'navReorder' }
      });
    }
  }
  return rows;
}

/* Compare le bloc "visibility" (interrupteurs). */
function diffVisibility(oldObj, newObj){
  oldObj = oldObj || {}; newObj = newObj || {};
  const rows = [];
  const keys = Array.from(new Set([...Object.keys(oldObj), ...Object.keys(newObj)]));
  keys.forEach(k => {
    if (!!oldObj[k] === !!newObj[k]) return;
    const label = DIFF_VISIBILITY_LABELS[k] || k;
    const fmt = v => v ? 'affichée' : 'masquée';
    rows.push({ type:'modified', html: `<strong>${escapeHtml(label)}</strong> : ${fmt(oldObj[k])}<span class="diff-arrow">→</span><span class="${newObj[k] ? 'diff-new' : 'diff-old'}">${fmt(newObj[k])}</span>`, label: `${label} : ${fmt(oldObj[k])} → ${fmt(newObj[k])}`, revert: { kind:'field', section:'visibility', field:k } });
  });
  return rows;
}

/* =====================================================================
   ANNULATION CIBLÉE D'UNE SEULE LIGNE DE DIFF
   Applique, sur CONTENT, l'inverse exact d'un changement décrit par un
   descripteur `revert` (voir les fonctions diff* ci-dessus). La
   référence « valeur d'origine » est toujours PRISTINE_CONTENT, qui
   correspond dans les deux points d'entrée (aperçu et publication) au
   contenu actuellement en ligne au moment du calcul du diff.
   ===================================================================== */
function applyRowRevert(r){
  if (!r || !PRISTINE_CONTENT || !CONTENT) return false;
  const clone = v => (v === undefined ? undefined : JSON.parse(JSON.stringify(v)));
  switch (r.kind){
    case 'field': {
      if (!r.section) return false;
      CONTENT[r.section] = CONTENT[r.section] || {};
      const oldVal = (PRISTINE_CONTENT[r.section] || {})[r.field];
      if (oldVal === undefined) delete CONTENT[r.section][r.field];
      else CONTENT[r.section][r.field] = clone(oldVal);
      if (r.section === 'theme'){
        ORIGINAL_THEME = JSON.parse(JSON.stringify(CONTENT.theme));
        applyTheme(CONTENT.theme);
      }
      return true;
    }
    case 'themeAll': {
      if (!PRISTINE_CONTENT.theme) return false;
      CONTENT.theme = clone(PRISTINE_CONTENT.theme);
      ORIGINAL_THEME = JSON.parse(JSON.stringify(CONTENT.theme));
      applyTheme(CONTENT.theme);
      return true;
    }
    case 'stringArray': {
      if (!r.section) return false;
      CONTENT[r.section] = CONTENT[r.section] || [];
      if (r.action === 'remove'){
        CONTENT[r.section] = CONTENT[r.section].filter(v => v !== r.value);
      } else if (!CONTENT[r.section].includes(r.value)){
        const oldArr = PRISTINE_CONTENT[r.section] || [];
        const idx = oldArr.indexOf(r.value);
        if (idx >= 0 && idx <= CONTENT[r.section].length) CONTENT[r.section].splice(idx, 0, r.value);
        else CONTENT[r.section].push(r.value);
      }
      return true;
    }
    case 'stringArrayReorder': {
      /* Ne touche qu'à l'ordre : les éléments ajoutés/supprimés restent
         tels quels. Les éléments présents dans PRISTINE_CONTENT reprennent
         leur ordre relatif d'origine ; les nouveaux gardent leur position
         relative actuelle, à la suite — même logique que galerieReorder. */
      if (!r.section) return false;
      const oldArr = PRISTINE_CONTENT[r.section] || [];
      const cur = CONTENT[r.section] || [];
      const oldPos = new Map(oldArr.map((v, i) => [v, i]));
      const withKey = cur.map((v, i) => ({ v, key: oldPos.has(v) ? oldPos.get(v) : (1e6 + i) }));
      withKey.sort((a, b) => a.key - b.key);
      CONTENT[r.section] = withKey.map(x => x.v);
      return true;
    }
    case 'serviceField': {
      const svc = (CONTENT.services || []).find(s => s.titre === r.titre);
      const oldSvc = (PRISTINE_CONTENT.services || []).find(s => s.titre === r.titre);
      if (!svc || !oldSvc) return false;
      svc[r.field] = clone(oldSvc[r.field]);
      return true;
    }
    case 'serviceRemove': {
      CONTENT.services = (CONTENT.services || []).filter(s => s.titre !== r.titre);
      return true;
    }
    case 'serviceRestore': {
      const oldArr = PRISTINE_CONTENT.services || [];
      const oldSvc = oldArr.find(s => s.titre === r.titre);
      if (!oldSvc) return false;
      CONTENT.services = CONTENT.services || [];
      if (CONTENT.services.some(s => s.titre === r.titre)) return true;
      const insertAt = Math.min(oldArr.indexOf(oldSvc), CONTENT.services.length);
      CONTENT.services.splice(insertAt, 0, clone(oldSvc));
      return true;
    }
    case 'serviceReorder': {
      const oldArr = PRISTINE_CONTENT.services || [];
      const cur = CONTENT.services || [];
      const oldPos = new Map(oldArr.map((s, i) => [s.titre, i]));
      const withKey = cur.map((s, i) => ({ s, key: (s && oldPos.has(s.titre)) ? oldPos.get(s.titre) : (1e6 + i) }));
      withKey.sort((a, b) => a.key - b.key);
      CONTENT.services = withKey.map(x => x.s);
      return true;
    }
    case 'galerieAlt': {
      const g = (CONTENT.galerie || []).find(x => x.src === r.src);
      const og = (PRISTINE_CONTENT.galerie || []).find(x => x.src === r.src);
      if (!g || !og) return false;
      g.alt = og.alt;
      return true;
    }
    case 'galerieRemove': {
      CONTENT.galerie = (CONTENT.galerie || []).filter(x => x.src !== r.src);
      return true;
    }
    case 'galerieRestore': {
      const oldArr = PRISTINE_CONTENT.galerie || [];
      const og = oldArr.find(x => x.src === r.src);
      if (!og) return false;
      CONTENT.galerie = CONTENT.galerie || [];
      if (CONTENT.galerie.some(x => x.src === r.src)) return true;
      const insertAt = Math.min(oldArr.indexOf(og), CONTENT.galerie.length);
      CONTENT.galerie.splice(insertAt, 0, clone(og));
      return true;
    }
    case 'galerieReplace': {
      const og = (PRISTINE_CONTENT.galerie || [])[r.index];
      if (!og || !CONTENT.galerie || !CONTENT.galerie[r.index]) return false;
      CONTENT.galerie[r.index] = clone(og);
      return true;
    }
    case 'galerieReorder': {
      /* Ne touche qu'à l'ordre : les photos ajoutées/supprimées/remplacées
         restent telles quelles. Les photos aussi présentes dans la version
         d'origine reprennent leur ordre relatif d'origine ; les autres
         (nouvelles) gardent leur position relative actuelle, à la suite. */
      const oldArr = PRISTINE_CONTENT.galerie || [];
      const cur = CONTENT.galerie || [];
      const oldPos = new Map(oldArr.filter(g => g && g.src).map((g, i) => [g.src, i]));
      const withKey = cur.map((g, i) => ({ g, key: (g && g.src && oldPos.has(g.src)) ? oldPos.get(g.src) : (1e6 + i) }));
      withKey.sort((a, b) => a.key - b.key);
      CONTENT.galerie = withKey.map(x => x.g);
      return true;
    }
    case 'navField': {
      const n = (CONTENT.nav || []).find(x => x.id === r.id);
      const on = (PRISTINE_CONTENT.nav || []).find(x => x.id === r.id);
      if (!n || !on) return false;
      n[r.field] = on[r.field];
      return true;
    }
    case 'navRemove': {
      CONTENT.nav = (CONTENT.nav || []).filter(x => x.id !== r.id);
      return true;
    }
    case 'navRestore': {
      const oldArr = PRISTINE_CONTENT.nav || [];
      const on = oldArr.find(x => x.id === r.id);
      if (!on) return false;
      CONTENT.nav = CONTENT.nav || [];
      if (CONTENT.nav.some(x => x.id === r.id)) return true;
      const insertAt = Math.min(oldArr.indexOf(on), CONTENT.nav.length);
      CONTENT.nav.splice(insertAt, 0, clone(on));
      return true;
    }
    case 'navReorder': {
      const oldArr = PRISTINE_CONTENT.nav || [];
      const cur = CONTENT.nav || [];
      const oldPos = new Map(oldArr.map((n, i) => [n.id, i]));
      const withKey = cur.map((n, i) => ({ n, key: (n && oldPos.has(n.id)) ? oldPos.get(n.id) : (1e6 + i) }));
      withKey.sort((a, b) => a.key - b.key);
      CONTENT.nav = withKey.map(x => x.n);
      return true;
    }
    default: return false;
  }
}

/* Réduit le résultat de diffContent() à un simple comptage
   { added, removed, modified, total } — utilisé par la modale de
   comparaison ET par les pastilles de résumé de la liste des
   anciennes versions. */
function summarizeDiffCounts(changes){
  let added = 0, removed = 0, modified = 0;
  (changes || []).forEach(s => s.rows.forEach(r => {
    if (r.type === 'added') added++;
    else if (r.type === 'removed') removed++;
    else modified++;
  }));
  return { added, removed, modified, total: added + removed + modified };
}
/* Rendu HTML des pastilles ＋ / ～ / － à partir d'un comptage. Chaque
   pastille porte data-diff-type et sert de filtre cliquable dans
   showDiffModal (voir DIFF_ACTIVE_FILTERS). */
function diffCountPillsHtml({ added, removed, modified }){
  const pill = (n, type, cls, icon, label) => n
    ? `<span class="diff-stat ${cls}" data-diff-type="${type}" role="button" tabindex="0" title="Afficher/masquer les ${label}s">${icon} ${n} ${label}${n > 1 ? 's' : ''}</span>` : '';
  return pill(added, 'added', 'is-added', '＋', 'ajout') + pill(modified, 'modified', 'is-modified', '～', 'modification') + pill(removed, 'removed', 'is-removed', '－', 'suppression');
}
/* Résumé "quelles sections ont changé" (ex. "Thème, Galerie photo,
   Présentation") plutôt qu'un simple chiffre — se comprend d'un coup
   d'œil sans avoir à ouvrir le détail. Volontairement plafonné : au-delà
   de `maxNames`, une liste de noms devient aussi peu lisible qu'un
   chiffre, donc on résume avec "et N autres" plutôt que de tout lister. */
function summarizeSectionNames(changes, maxNames = 4){
  const names = (changes || []).map(s => s.title).filter(Boolean);
  if (!names.length) return '';
  if (names.length <= maxNames) return names.join(', ');
  const rest = names.length - maxNames;
  return names.slice(0, maxNames).join(', ') + ' et ' + rest + ' autre' + (rest > 1 ? 's' : '');
}

/* =====================================================================
   PASTILLES "X MODIFICATIONS" SUR CHAQUE SECTION (repliée ou non)
   Compare CONTENT à PRISTINE_CONTENT (la version réellement en ligne)
   et affiche, sur l'en-tête de chaque panneau concerné, le nombre de
   changements en attente — visible même section repliée.
   ===================================================================== */
/* Un panneau peut regrouper plusieurs clés de contenu à la fois (ex. le
   panneau « Galerie photo » couvre à la fois `galerie` — les photos — et
   `galerieTexte` — titre + type d'affichage) : `data-diff-section` accepte
   une liste séparée par des espaces, et sa pastille additionne les
   changements de toutes les clés listées. */
let sectionBadgeDebounceTimer = null;

function updateSectionDiffBadges(){
  if (!PRISTINE_CONTENT || !CONTENT) return;
  let changes = [];
  try {
    changes = diffContent(PRISTINE_CONTENT, CONTENT);
  } catch(e){
    // Un état transitoire incohérent ne doit jamais casser l'affichage —
    // les pastilles se remettront à jour au prochain changement valide.
    return;
  }
  const countByKey = {};
  changes.forEach(s => { countByKey[s.key] = s.rows.length; });
  $$('[data-diff-section]').forEach(panel => {
    const keys = panel.dataset.diffSection.split(/\s+/).filter(Boolean);
    const badge = panel.querySelector('[data-diff-badge]');
    const restoreBtn = panel.querySelector('[data-restore-section]');
    if (!badge) return;
    const n = keys.reduce((sum, k) => sum + (countByKey[k] || 0), 0);
    if (n){
      badge.textContent = n === 1 ? '✎ 1 modification' : '✎ ' + n + ' modifications';
      badge.style.display = 'inline-flex';
      panel.classList.add('has-changes');
      if (restoreBtn) restoreBtn.style.display = 'inline-flex';
    } else {
      badge.style.display = 'none';
      panel.classList.remove('has-changes');
      if (restoreBtn) restoreBtn.style.display = 'none';
    }
  });

  /* Vue d'ensemble globale (en plus des pastilles par section) : quelles
     sections ont changé (par leur nom, plus parlant qu'un simple compte)
     + nombre total de changements en attente, visible avant même de
     tout déplier. Sert aussi de point d'entrée vers l'aperçu détaillé
     (previewPendingChanges). Calculée directement depuis `changes` (pas
     depuis les pastilles par panneau ci-dessus), donc toujours exacte
     même si une clé n'a pas (encore) de panneau dédié. */
  const globalIndicator = $('#globalDiffIndicator');
  const totalChanges = changes.reduce((sum, s) => sum + s.rows.length, 0);
  if (globalIndicator){
    if (totalChanges){
      const namesLabel = summarizeSectionNames(changes, 4);
      const changesLabel = totalChanges === 1 ? '1 changement au total' : totalChanges + ' changements au total';
      globalIndicator.textContent = namesLabel + ' — ' + changesLabel;
      globalIndicator.style.display = 'flex';
    } else {
      globalIndicator.style.display = 'none';
    }
  }
}

/* immediate:true pour un recalcul instantané (chargement, undo/redo,
   publication…) ; sinon regroupe les frappes successives comme pour
   l'historique annuler/rétablir. */
function scheduleSectionBadgesUpdate(immediate){
  clearTimeout(sectionBadgeDebounceTimer);
  sectionBadgeDebounceTimer = null;
  if (immediate){
    updateSectionDiffBadges();
    return;
  }
  sectionBadgeDebounceTimer = setTimeout(updateSectionDiffBadges, 450);
}

/* =====================================================================
   APERÇU DES CHANGEMENTS EN ATTENTE — la même modale de comparaison que
   celle de la publication, mais accessible à la demande (indicateur
   global ou bouton dédié), sans déclencher la vérification des photos
   ni le mot de passe. Si l'utilisatrice confirme depuis l'aperçu, on
   enchaîne directement sur le vrai circuit de publication.
   ===================================================================== */
async function previewPendingChanges(){
  if (!PRISTINE_CONTENT || !CONTENT) return;
  let changes = [];
  try {
    changes = diffContent(PRISTINE_CONTENT, CONTENT);
  } catch(e){
    showToast('Impossible de calculer l\u2019aperçu des changements pour le moment.', true);
    return;
  }
  if (!changes.length){
    showToast('Aucune modification en attente — tout est déjà identique à la version en ligne.');
    return;
  }
  const proceed = await showDiffModal({
    icon: '🔍',
    title: 'Changements en attente',
    subtitle: 'Voici tout ce qui a été modifié depuis la version actuellement en ligne. Cette fenêtre ne publie rien — ferme-la pour continuer à éditer, ou lance la publication directement.',
    changes,
    confirmLabel: '🚀 Publier maintenant',
    cancelLabel: 'Fermer'
  });
  if (proceed) publishContent();
}
const globalDiffIndicatorEl = $('#globalDiffIndicator');
if (globalDiffIndicatorEl){
  globalDiffIndicatorEl.addEventListener('click', previewPendingChanges);
  globalDiffIndicatorEl.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' '){ e.preventDefault(); previewPendingChanges(); }
  });
}

/* =====================================================================
   RESTAURER AUX VALEURS ACTUELLEMENT EN LIGNE (PRISTINE_CONTENT)
   Un bouton par section (visible uniquement si elle a des changements
   non publiés) + un bouton global. Toujours confirmé, car destructif
   pour les modifications en cours.
   ===================================================================== */
async function restoreSectionFromPristine(keysAttr){
  if (!PRISTINE_CONTENT || !CONTENT) return;
  const keys = String(keysAttr || '').split(/\s+/).filter(Boolean);
  if (!keys.length) return;
  const label = keys.map(k => DIFF_SECTION_LABELS[k] || k).join(' + ');
  const ok = await customConfirm({
    icon: '↺',
    title: 'Restaurer cette section ?',
    message: `« ${label} » reviendra aux valeurs actuellement en ligne sur le site. Les modifications non publiées de cette section seront perdues.`,
    okText: 'Restaurer cette section',
    cancelText: 'Annuler',
    danger: true
  });
  if (!ok) return;

  keys.forEach(key => { CONTENT[key] = JSON.parse(JSON.stringify(PRISTINE_CONTENT[key])); });
  renderAll();
  if (keys.includes('theme')){
    ORIGINAL_THEME = JSON.parse(JSON.stringify(CONTENT.theme));
    applyTheme(CONTENT.theme);
  }
  commitChange('Restauration de la section « ' + label + ' »');
  showToast('« ' + label + ' » restaurée aux valeurs en ligne.');
}

$$('[data-restore-section]').forEach(btn => {
  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    restoreSectionFromPristine(btn.dataset.restoreSection);
  });
});

async function restoreAllFromPristine(){
  if (!PRISTINE_CONTENT || !CONTENT) return;
  const ok = await customConfirm({
    icon: '↺',
    title: 'Tout restaurer ?',
    message: 'Tout le contenu reviendra aux valeurs actuellement en ligne sur le site. Toutes tes modifications non publiées seront perdues, dans toutes les sections.',
    okText: 'Tout restaurer',
    cancelText: 'Annuler',
    danger: true
  });
  if (!ok) return;

  CONTENT = JSON.parse(JSON.stringify(PRISTINE_CONTENT));
  ORIGINAL_THEME = JSON.parse(JSON.stringify(CONTENT.theme));
  renderAll();
  applyTheme(CONTENT.theme);
  commitChange('Restauration de tout le contenu');
  showToast('Tout le contenu a été restauré aux valeurs en ligne.');
}
const restoreAllBtnEl = $('#restoreAllBtn');
if (restoreAllBtnEl) restoreAllBtnEl.addEventListener('click', restoreAllFromPristine);

/* Point d'entrée : compare deux contenus complets et retourne
   [{ title, rows: [...] }] en ne gardant que les sections modifiées. */
/* Un préréglage de palette change les 9 couleurs à la fois : les
   comparer une par une donnerait 9 lignes de diff pour ce qui est en
   réalité une seule action (« appliquer telle palette »). On détecte
   donc d'abord si le nouveau thème correspond exactement à un
   THEME_PRESETS connu (et que l'ancien n'était pas déjà ce même
   préréglage) pour n'afficher qu'une seule ligne dans ce cas ; sinon on
   retombe sur la comparaison champ par champ habituelle (ex. une seule
   couleur retouchée à la main). */
function detectThemePreset(themeObj){
  if (!themeObj) return null;
  return THEME_PRESETS.find(p => THEME_FIELDS.every(f => (themeObj[f.key] || '').toLowerCase() === (p.theme[f.key] || '').toLowerCase())) || null;
}
function diffTheme(oldObj, newObj){
  oldObj = oldObj || {}; newObj = newObj || {};
  const newPreset = detectThemePreset(newObj);
  if (newPreset){
    const oldPreset = detectThemePreset(oldObj);
    if (oldPreset && oldPreset.name === newPreset.name) return [];
    return [{
      type:'modified',
      html: `Palette de couleurs changée pour <strong>« ${escapeHtml(newPreset.name)} »</strong> (${THEME_FIELDS.length} couleurs à la fois)`,
      label: `Palette appliquée : ${newPreset.name}`,
      revert: { kind:'themeAll' }
    }];
  }
  return diffFlatObject(oldObj, newObj, null, { section:'theme' });
}
function diffContent(oldContent, newContent){
  oldContent = oldContent || {}; newContent = newContent || {};
  const sections = [];
  const push = (key, rows) => { if (rows && rows.length) sections.push({ key, title: DIFF_SECTION_LABELS[key] || key, rows }); };

  push('visibility', diffVisibility(oldContent.visibility, newContent.visibility));
  push('contact', diffFlatObject(oldContent.contact, newContent.contact, null, { section:'contact' }));
  push('hero', diffFlatObject(oldContent.hero, newContent.hero, null, { section:'hero' }));
  push('presentation', diffFlatObject(oldContent.presentation, newContent.presentation, null, { section:'presentation' }));
  push('pourquoi', diffFlatObject(oldContent.pourquoi, newContent.pourquoi, null, { section:'pourquoi' }));
  push('tags', diffStringArray(oldContent.tags, newContent.tags, 'tags'));
  push('services', diffServices(oldContent.services, newContent.services));
  push('images', diffFlatObject(oldContent.images, newContent.images, null, { section:'images', imageFields:['hero','contact','portrait'] }));
  push('galerie', diffGalerie(oldContent.galerie, newContent.galerie));
  push('galerieTexte', diffFlatObject(oldContent.galerieTexte, newContent.galerieTexte, null, { section:'galerieTexte' }));
  push('nav', diffNav(oldContent.nav, newContent.nav));
  push('theme', diffTheme(oldContent.theme, newContent.theme));
  push('footer', diffFlatObject(oldContent.footer, newContent.footer, null, { section:'footer' }));
  push('seo', diffFlatObject(oldContent.seo, newContent.seo, null, { section:'seo' }));

  return sections;
}

/* =====================================================================
   MODALE DE COMPARAISON — affiche `changes` (sortie de diffContent),
   permet d'annuler une ligne (ou toute une section) directement depuis
   la modale, et résout `true`/`false` selon le bouton final cliqué.

   L'annulation d'une ligne agit sur CONTENT via applyRowRevert (la
   référence « avant » est toujours PRISTINE_CONTENT — voir plus haut),
   puis le diff est recalculé et la modale se redessine sur place, sans
   se refermer, avec un petit compteur qui décroît en direct.
   ===================================================================== */
function showDiffModal({ icon = '✨', title, subtitle = '', changes = [], confirmLabel = 'Valider', cancelLabel = 'Annuler' }){
  return new Promise(resolve => {
    const overlay = $('#diffOverlay');
    const body = $('#diffBody');
    const stats = $('#diffStats');
    const okBtn = $('#diffOkBtn');
    const cancelBtn = $('#diffCancelBtn');
    const closeBtn = $('#diffCloseBtn');

    $('#diffIcon').textContent = icon;
    $('#diffTitle').textContent = title;
    $('#diffSubtitle').textContent = subtitle;

    let flatRows = [];        // index plat -> { row, sectionKey } pour le délégué de clic
    const activeFilters = new Set(); // types désactivés (filtre) — vide = tout affiché

    function render(currentChanges){
      const { added, removed, modified, total: totalRows } = summarizeDiffCounts(currentChanges);
      flatRows = [];

      stats.innerHTML = '';
      if (totalRows){
        stats.insertAdjacentHTML('beforeend', diffCountPillsHtml({ added, removed, modified }));
        if (currentChanges.length > 1 || totalRows > 4){
          stats.insertAdjacentHTML('beforeend', `<span class="diff-stats-hint">Clique une pastille pour filtrer, ou l’icône ↺ d’une ligne pour l’annuler seule.</span>`);
        }
      }
      const sectionsSummaryEl = $('#diffSectionsSummary');
      if (sectionsSummaryEl){
        sectionsSummaryEl.textContent = totalRows ? summarizeSectionNames(currentChanges) : '';
        sectionsSummaryEl.style.display = totalRows ? 'block' : 'none';
      }

      if (!totalRows){
        body.innerHTML = changes.length
          ? `<div class="diff-all-undone"><div class="diff-all-undone-icon">✅</div>Toutes les modifications ont été annulées — le contenu correspond de nouveau à la version en ligne.</div>`
          : `<div class="diff-empty"><div class="diff-empty-icon">🤷</div>Aucune différence détectée — tout est déjà identique.</div>`;
      } else {
        let rowIndex = 0;
        body.innerHTML = currentChanges.map(section => {
          const sectionIcon = DIFF_SECTION_ICONS[section.key] || '📌';
          const canUndoSection = section.rows.some(r => r.revert);
          const rowsHtml = section.rows.map(r => {
            const flatIdx = flatRows.length;
            flatRows.push(r);
            const delay = (rowIndex++ * 0.035).toFixed(3);
            const isPhoto = r.variant === 'photo' || r.variant === 'cadrage';
            const undoBtn = r.revert ? `<button type="button" class="diff-row-undo" data-row-idx="${flatIdx}" title="Annuler uniquement cette modification" aria-label="Annuler uniquement cette modification">↺</button>` : '';
            return `
              <div class="diff-row ${r.type}${isPhoto ? ' is-photo' : ''}" data-diff-type="${r.type}" style="animation-delay:${delay}s;">
                <span class="diff-row-badge">${r.type === 'added' ? '+' : (r.type === 'removed' ? '−' : '~')}</span>
                <span class="diff-row-text">${r.html}</span>
                ${undoBtn}
              </div>`;
          }).join('');
          return `
          <div class="diff-section" data-section-key="${escapeHtml(section.key)}">
            <p class="diff-section-title">
              <span class="diff-section-icon">${sectionIcon}</span>${escapeHtml(section.title)}<span class="diff-section-count">${section.rows.length}</span>
              ${canUndoSection ? `<button type="button" class="diff-section-undo" data-section-undo="${escapeHtml(section.key)}">↺ Tout annuler</button>` : ''}
            </p>
            ${rowsHtml}
          </div>`;
        }).join('');
      }
      applyFilters();
      checkPhotoRenames(body);

      $('#diffCount').textContent = totalRows
        ? (totalRows === 1 ? '1 modification au total' : totalRows + ' modifications au total')
        : '';
      okBtn.textContent = confirmLabel;
      okBtn.style.display = totalRows ? '' : 'none';
      cancelBtn.textContent = totalRows ? cancelLabel : 'Fermer';
    }

    function applyFilters(){
      $$('.diff-stat[data-diff-type]', stats).forEach(el => {
        el.classList.toggle('is-off', activeFilters.has(el.dataset.diffType));
      });
      $$('.diff-row[data-diff-type]', body).forEach(el => {
        el.style.display = activeFilters.has(el.dataset.diffType) ? 'none' : '';
      });
      $$('.diff-section', body).forEach(sec => {
        const anyVisible = $$('.diff-row', sec).some(el => el.style.display !== 'none');
        sec.style.display = anyVisible ? '' : 'none';
      });
    }

    let liveChanges = changes;

    async function handleRowRevert(idx){
      const row = flatRows[idx];
      if (!row || !row.revert) return;
      const rowEl = body.querySelector(`.diff-row-undo[data-row-idx="${idx}"]`)?.closest('.diff-row');
      if (rowEl) rowEl.classList.add('is-reverting');
      const ok = applyRowRevert(row.revert);
      if (!ok){ if (rowEl) rowEl.classList.remove('is-reverting'); showToast('Impossible d\u2019annuler cette modification.', true); return; }
      renderAll();
      commitChange('Annulation cibl\u00e9e : ' + (row.label || 'une modification'));
      showToast('Modification annul\u00e9e : ' + (row.label || ''));
      try { liveChanges = diffContent(PRISTINE_CONTENT, CONTENT); } catch(e){ liveChanges = []; }
      render(liveChanges);
      body.scrollTop = Math.max(0, body.scrollTop);
    }

    async function handleSectionUndo(sectionKey){
      const section = liveChanges.find(s => s.key === sectionKey);
      if (!section) return;
      const label = section.title;
      let anyOk = false;
      section.rows.forEach(r => { if (r.revert && applyRowRevert(r.revert)) anyOk = true; });
      if (!anyOk) return;
      renderAll();
      commitChange('Annulation de toutes les modifications de la section « ' + label + ' »');
      showToast('Section « ' + label + ' » annul\u00e9e.');
      try { liveChanges = diffContent(PRISTINE_CONTENT, CONTENT); } catch(e){ liveChanges = []; }
      render(liveChanges);
    }

    render(liveChanges);

    overlay.style.display = 'flex';
    requestAnimationFrame(() => overlay.classList.add('show'));
    body.scrollTop = 0;

    function cleanup(result){
      overlay.classList.remove('show');
      setTimeout(() => { overlay.style.display = 'none'; }, 300);
      okBtn.removeEventListener('click', onOk);
      cancelBtn.removeEventListener('click', onCancel);
      if (closeBtn) closeBtn.removeEventListener('click', onCancel);
      overlay.removeEventListener('click', onOverlayClick);
      body.removeEventListener('click', onBodyClick);
      stats.removeEventListener('click', onStatsClick);
      document.removeEventListener('keydown', onKeydown, true);
      resolve(result);
    }
    function onOk(){ cleanup(true); }
    function onCancel(){ cleanup(false); }
    function onOverlayClick(e){ if (e.target === overlay) cleanup(false); }
    function onBodyClick(e){
      const undoBtn = e.target.closest('.diff-row-undo');
      if (undoBtn){ handleRowRevert(+undoBtn.dataset.rowIdx); return; }
      const sectionUndoBtn = e.target.closest('.diff-section-undo');
      if (sectionUndoBtn){ handleSectionUndo(sectionUndoBtn.dataset.sectionUndo); return; }
    }
    function onStatsClick(e){
      const pill = e.target.closest('.diff-stat[data-diff-type]');
      if (!pill) return;
      const t = pill.dataset.diffType;
      if (activeFilters.has(t)) activeFilters.delete(t); else activeFilters.add(t);
      applyFilters();
    }
    function onKeydown(e){ if (e.key === 'Escape'){ e.preventDefault(); cleanup(false); } }

    okBtn.addEventListener('click', onOk);
    cancelBtn.addEventListener('click', onCancel);
    if (closeBtn) closeBtn.addEventListener('click', onCancel);
    overlay.addEventListener('click', onOverlayClick);
    body.addEventListener('click', onBodyClick);
    stats.addEventListener('click', onStatsClick);
    stats.addEventListener('keydown', (e) => { if ((e.key === 'Enter' || e.key === ' ') && e.target.closest('.diff-stat')){ e.preventDefault(); e.target.closest('.diff-stat').click(); } });
    document.addEventListener('keydown', onKeydown, true);
  });
}

/* =====================================================================
   ANCIENNES VERSIONS — liste, résumé des différences, rechargement et
   suppression.
   BACKUPS_DATA : dernière liste chargée (avec état de sélection).
   BACKUP_CACHE : { [file]: { content, changes, counts } } — évite de
   retélécharger deux fois la même sauvegarde (résumé dans la liste
   puis clic sur "Comparer & charger").
   ===================================================================== */
let BACKUPS_DATA = [];
/* BACKUPS_LOADED : true dès qu'un premier chargement a réussi (permet le
   chargement automatique à la première ouverture du panneau, sans passer
   par le bouton « Rafraîchir »). BACKUPS_STALE : mis à true par toute
   action ailleurs dans l'admin susceptible d'avoir changé la liste sur le
   serveur (publication, suppression en cascade…) — force un rechargement
   au prochain moment pertinent (panneau déjà ouvert : tout de suite ;
   sinon : à la prochaine ouverture). */
let BACKUPS_LOADED = false;
let BACKUPS_STALE = false;
function invalidateBackups(){
  BACKUPS_STALE = true;
  const panel = document.querySelector('[data-lazy-panel="backups"]');
  if (panel && panel.classList.contains('open')) loadBackupsList();
}
const BACKUP_CACHE = {};

/* Nom parlant + date complète à partir d'un timestamp Unix (mtime). */
function formatBackupLabel(mtimeSeconds){
  const d = new Date(mtimeSeconds * 1000);
  const startOfDay = dt => new Date(dt.getFullYear(), dt.getMonth(), dt.getDate()).getTime();
  const diffDays = Math.round((startOfDay(new Date()) - startOfDay(d)) / 86400000);
  const time = d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
  const fullDate = d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
  let label;
  if (diffDays === 0) label = 'Aujourd\u2019hui à ' + time;
  else if (diffDays === 1) label = 'Hier à ' + time;
  else if (diffDays > 1 && diffDays < 7){
    const weekday = d.toLocaleDateString('fr-FR', { weekday: 'long' });
    label = weekday.charAt(0).toUpperCase() + weekday.slice(1) + ' à ' + time;
  } else {
    label = fullDate + ' à ' + time;
  }
  return { label, full: fullDate + ' à ' + time };
}

function updateBackupsToolbarState(){
  const selected = BACKUPS_DATA.filter(b => b.selected);
  const delBtn = $('#deleteBackupsBtn');
  delBtn.disabled = !selected.length;
  delBtn.textContent = selected.length
    ? '🗑 Supprimer la sélection (' + selected.length + ')'
    : '🗑 Supprimer la sélection';
}

/* Recherche/filtre des anciennes versions par date — n'apparaît que si
   la liste s'allonge, pour ne pas encombrer l'écran quand il y a peu
   de sauvegardes. Filtre en direct sur les cartes déjà rendues (pas de
   nouvelle requête réseau), en comparant au libellé affiché (« Hier »,
   « Mardi », date complète, etc.). */
const BACKUPS_SEARCH_THRESHOLD = 6;
function updateBackupsSearchVisibility(){
  const wrap = $('#backupsSearchWrap');
  if (!wrap) return;
  const shouldShow = BACKUPS_DATA.length >= BACKUPS_SEARCH_THRESHOLD;
  wrap.style.display = shouldShow ? 'block' : 'none';
  if (!shouldShow){
    const input = $('#backupsSearchInput');
    if (input) input.value = '';
    filterBackupsList('');
  }
}
function filterBackupsList(rawQuery){
  const query = (rawQuery || '').trim().toLowerCase();
  const list = $('#backupsList');
  const emptyMsg = $('#backupsSearchEmpty');
  if (!list) return;
  let visibleCount = 0;
  $$('.bkp-card', list).forEach(card => {
    const b = BACKUPS_DATA.find(x => x.file === card.dataset.file);
    const haystack = b ? (formatBackupLabel(b.mtime).label + ' ' + formatBackupLabel(b.mtime).full).toLowerCase() : '';
    const match = !query || haystack.includes(query);
    card.style.display = match ? '' : 'none';
    if (match) visibleCount++;
  });
  if (emptyMsg) emptyMsg.style.display = (query && !visibleCount) ? 'block' : 'none';
}
const backupsSearchInputEl = $('#backupsSearchInput');
if (backupsSearchInputEl){
  backupsSearchInputEl.addEventListener('input', (e) => filterBackupsList(e.target.value));
}

async function loadBackupsList(){
  const list = $('#backupsList');
  $('#backupsToolbar').style.display = 'none';
  list.innerHTML = '<div class="desc" style="display:flex; align-items:center; gap:8px;"><div class="spinner-sm"></div> Chargement…</div>';
  try {
    const res = await fetchWithTimeout('inc/list-backups.php', { credentials: 'same-origin' }, 15000);
    const data = await readJsonSafe(res);
    if (res.status === 401){
      list.innerHTML = `<div class="desc">Session expirée — recharge la page pour te reconnecter.</div>`;
      return;
    }
    if (!res.ok || !data || !data.ok){
      list.innerHTML = `<div class="desc">Impossible de récupérer la liste des anciennes versions${data && data.message ? ' : ' + escapeHtml(data.message) : ''}. <button type="button" class="btn btn-outline btn-sm" id="retryBackupsBtn" style="margin-top:8px;">↻ Réessayer</button></div>`;
      const retryBtn = $('#retryBackupsBtn');
      if (retryBtn) retryBtn.addEventListener('click', loadBackupsList);
      return;
    }
    if (!data.backups.length){
      list.innerHTML = `<div class="desc">Aucune ancienne version pour l\u2019instant — elles apparaîtront ici après ta première publication.</div>`;
      BACKUPS_DATA = [];
      BACKUPS_LOADED = true;
      BACKUPS_STALE = false;
      return;
    }
    BACKUPS_DATA = data.backups.map(b => Object.assign({ selected: false }, b));
    BACKUPS_LOADED = true;
    BACKUPS_STALE = false;
    renderBackupsList();
    $('#backupsToolbar').style.display = 'flex';
    loadBackupDiffSummaries();
  } catch(e){
    list.innerHTML = `<div class="desc">${escapeHtml(e.message || 'Erreur réseau — réessaie.')} <button type="button" class="btn btn-outline btn-sm" id="retryBackupsBtn2" style="margin-top:8px;">↻ Réessayer</button></div>`;
    const retryBtn = $('#retryBackupsBtn2');
    if (retryBtn) retryBtn.addEventListener('click', loadBackupsList);
  }
}
$('#refreshBackupsBtn').addEventListener('click', loadBackupsList);

function renderBackupsList(){
  const list = $('#backupsList');
  list.innerHTML = '';
  BACKUPS_DATA.forEach((b, idx) => {
    const { label, full } = formatBackupLabel(b.mtime);
    const card = document.createElement('div');
    card.className = 'bkp-card' + (b.selected ? ' is-selected' : '');
    card.dataset.file = b.file;
    card.innerHTML = `
      <div class="bkp-card-top">
        <label class="bkp-check"><input type="checkbox" ${b.selected ? 'checked' : ''} aria-label="Sélectionner cette version"></label>
        <div class="bkp-card-info">
          <div class="bkp-card-title">🗓 ${escapeHtml(label)}${idx === 0 ? '<span class="bkp-badge-new">Plus récente</span>' : ''}</div>
          <div class="bkp-card-meta">${escapeHtml(full)} · ${formatFileSize(b.size)}</div>
        </div>
        <button type="button" class="bkp-quick-delete" title="Supprimer définitivement cette version" aria-label="Supprimer définitivement cette version">🗑</button>
      </div>
      <div class="bkp-card-summary"><div class="spinner-sm"></div> Comparaison avec le contenu actuel…</div>
      <div class="bkp-card-actions">
        <button class="btn btn-outline btn-sm" type="button">👁 Comparer & charger</button>
      </div>
    `;
    card.querySelector('input[type=checkbox]').addEventListener('change', (e) => {
      b.selected = e.target.checked;
      card.classList.toggle('is-selected', b.selected);
      updateBackupsToolbarState();
    });
    card.querySelector('.bkp-quick-delete').addEventListener('click', () => deleteBackups([b.file]));
    card.querySelector('.bkp-card-actions .btn').addEventListener('click', () => loadBackupWithDiff(b.file));
    list.appendChild(card);
  });
  updateBackupsToolbarState();
  updateBackupsSearchVisibility();
  const searchInput = $('#backupsSearchInput');
  if (searchInput && searchInput.value) filterBackupsList(searchInput.value);
}

$('#selectAllBackupsBtn').addEventListener('click', () => {
  BACKUPS_DATA.forEach(b => b.selected = true);
  renderBackupsList();
});
$('#selectNoneBackupsBtn').addEventListener('click', () => {
  BACKUPS_DATA.forEach(b => b.selected = false);
  renderBackupsList();
});

/* Télécharge chaque sauvegarde en tâche de fond (une à la fois, pour ne
   pas saturer le serveur) et affiche son résumé de différences dès
   qu'il est prêt, directement dans la carte correspondante — sans
   attendre un clic sur "Comparer & charger". */
async function loadBackupDiffSummaries(){
  for (const b of BACKUPS_DATA){
    const card = $(`.bkp-card[data-file="${CSS.escape(b.file)}"]`);
    const summaryEl = card && card.querySelector('.bkp-card-summary');
    if (!summaryEl) continue;
    try {
      const changes = await fetchBackupDiff(b.file);
      const counts = summarizeDiffCounts(changes);
      summaryEl.innerHTML = counts.total
        ? diffCountPillsHtml(counts) + `<span class="bkp-diff-sections">${escapeHtml(summarizeSectionNames(changes))}</span>`
        : '<span class="bkp-diff-pill is-none">🤷 Identique au contenu actuel de l\u2019éditeur</span>';
    } catch(e){
      summaryEl.innerHTML = `<span class="bkp-diff-pill is-error">Résumé indisponible</span>`;
    }
  }
}

/* Télécharge (ou récupère du cache) le contenu d'une sauvegarde et
   calcule son diff par rapport à CONTENT. */
async function fetchBackupDiff(file){
  if (BACKUP_CACHE[file]) return BACKUP_CACHE[file].changes;
  const res = await fetchWithTimeout('inc/get-backup.php?file=' + encodeURIComponent(file), { credentials: 'same-origin' }, 20000);
  const data = await readJsonSafe(res);
  if (res.status === 401) throw new Error('Session expirée — recharge la page.');
  if (!res.ok || !data || !data.ok) throw new Error((data && data.message) || 'Impossible de charger cette version.');
  const changes = diffContent(CONTENT, data.content);
  BACKUP_CACHE[file] = { content: data.content, changes, counts: summarizeDiffCounts(changes) };
  return changes;
}

async function loadBackupWithDiff(file){
  showToast('Chargement de cette ancienne version…');
  try {
    const changes = await fetchBackupDiff(file);
    const backupContent = BACKUP_CACHE[file].content;

    let normalized;
    try {
      normalized = normalizeContent(backupContent);
    } catch(normErr){
      showToast('Cette sauvegarde ne peut pas être chargée : ' + (normErr.message || 'structure invalide') + '.', true);
      return;
    }

    const meta = BACKUPS_DATA.find(b => b.file === file);
    const friendlyName = meta ? formatBackupLabel(meta.mtime).full : file;

    const proceed = await showDiffModal({
      icon: '🗂',
      title: 'Charger cette ancienne version ?',
      subtitle: 'Voici ce qui changerait dans l\u2019éditeur si tu charges la version du ' + friendlyName + '. Rien n\u2019est publié tant que tu ne cliques pas ensuite sur « 🚀 Publier en ligne ».',
      changes,
      confirmLabel: '📂 Charger cette version',
      cancelLabel: 'Annuler'
    });
    if (!proceed) return;

    CONTENT = normalized;
    ORIGINAL_THEME = JSON.parse(JSON.stringify(CONTENT.theme));
    renderAll();
    applyTheme(CONTENT.theme);
    setDirty(true);
    seedHistory('Ancienne version chargée');
    scheduleSectionBadgesUpdate(true);
    reportRepairsIfAny();
    showToast('Ancienne version chargée dans l\u2019éditeur — pense à publier pour l\u2019appliquer au site.');
  } catch(e){
    showToast(e.message || 'Erreur lors du chargement de cette version.', true);
  }
}

/* Suppression définitive (sur le serveur) d'une ou plusieurs anciennes
   sauvegardes — n'affecte jamais le site publié, seulement l'historique. */
async function deleteBackups(files){
  if (!files.length) return;

  const rows = files.map(file => {
    const meta = BACKUPS_DATA.find(b => b.file === file);
    const label = meta ? formatBackupLabel(meta.mtime).full : file;
    return { type: 'removed', html: `<strong>${escapeHtml(label)}</strong>` };
  });

  const proceed = await showDiffModal({
    icon: '🗑',
    title: files.length > 1 ? `Supprimer ces ${files.length} anciennes versions ?` : 'Supprimer cette ancienne version ?',
    subtitle: 'Cette action est irréversible : ces sauvegardes seront supprimées du serveur. Le site publié et le contenu actuel de l\u2019éditeur ne sont pas concernés.',
    changes: [{ key: 'cleanup-backups', title: 'Versions supprimées définitivement', rows }],
    confirmLabel: '🗑 Supprimer définitivement',
    cancelLabel: 'Annuler'
  });
  if (!proceed) return;

  files.forEach(file => {
    const card = $(`.bkp-card[data-file="${CSS.escape(file)}"]`);
    if (card) card.classList.add('is-removing');
  });

  try {
    const res = await fetchWithTimeout('inc/delete-backups.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ files })
    }, 20000);
    const data = await readJsonSafe(res);
    if (res.status === 401){
      showToast('Session expirée — recharge la page.', true);
      return;
    }
    if (!res.ok || !data || !data.ok){
      showToast((data && data.message) || 'Échec de la suppression.', true);
      return;
    }
    const deleted = data.deleted || [];
    const skipped = data.skipped || [];
    deleted.forEach(f => delete BACKUP_CACHE[f]);
    BACKUPS_DATA = BACKUPS_DATA.filter(b => !deleted.includes(b.file));
    renderBackupsList();
    if (!BACKUPS_DATA.length){
      $('#backupsToolbar').style.display = 'none';
      $('#backupsList').innerHTML = `<div class="desc">Aucune ancienne version pour l\u2019instant — elles apparaîtront ici après ta prochaine publication.</div>`;
    }
    // Les mentions "N sauvegarde(s) impactée(s)" du panneau de nettoyage
    // peuvent référencer une sauvegarde qui vient de disparaître d'ici.
    if (deleted.length) invalidateUnusedPhotos();
    const parts = [];
    if (deleted.length) parts.push(deleted.length + ' version(s) supprimée(s)');
    if (skipped.length) parts.push(skipped.length + ' non supprimée(s)');
    showToast(parts.length ? parts.join(', ') + '.' : 'Rien n\u2019a été supprimé.', !deleted.length);
  } catch(e){
    showToast(e.message || 'Erreur réseau pendant la suppression.', true);
  } finally {
    files.forEach(file => {
      const card = $(`.bkp-card[data-file="${CSS.escape(file)}"]`);
      if (card) card.classList.remove('is-removing');
    });
  }
}

$('#deleteBackupsBtn').addEventListener('click', () => {
  const files = BACKUPS_DATA.filter(b => b.selected).map(b => b.file);
  deleteBackups(files);
});

/* =====================================================================
   NETTOYAGE DES PHOTOS INUTILISÉES (inc/list-unused-images.php,
   inc/delete-images.php) — repère les photos présentes sur le serveur
   mais non référencées dans le contenu en ligne, et permet de les
   supprimer après confirmation explicite. Toute ancienne sauvegarde
   qui référence encore une photo supprimée est supprimée avec elle.
   ===================================================================== */
let UNUSED_PHOTOS = [];
/* Même logique que BACKUPS_LOADED / BACKUPS_STALE ci-dessus, pour la
   recherche des photos inutilisées : premier scan automatique à
   l'ouverture du panneau, puis invalidation par tout ce qui peut changer
   le résultat côté serveur (envoi d'une nouvelle photo, publication qui
   libère une image remplacée…). */
let UNUSED_SCANNED = false;
let UNUSED_STALE = false;
function invalidateUnusedPhotos(){
  UNUSED_STALE = true;
  const panel = document.querySelector('[data-lazy-panel="unused"]');
  if (panel && panel.classList.contains('open')) scanUnusedPhotos();
}

async function scanUnusedPhotos(){
  const btn = $('#scanUnusedBtn');
  const originalLabel = btn.textContent;
  btn.disabled = true;
  btn.textContent = 'Recherche…';
  $('#unusedEmptyMsg').style.display = 'none';
  try {
    const res = await fetchWithTimeout('inc/list-unused-images.php', { credentials: 'same-origin' }, 25000);
    const data = await readJsonSafe(res);
    if (res.status === 401){
      showToast('Session expirée — recharge la page.', true);
      return;
    }
    if (!res.ok || !data || !data.ok){
      showToast((data && data.message) || 'Impossible de rechercher les photos inutilisées.', true);
      return;
    }
    UNUSED_PHOTOS = (data.unused || []).map(item => Object.assign({ selected: false }, item));
    UNUSED_SCANNED = true;
    UNUSED_STALE = false;
    if (!UNUSED_PHOTOS.length){
      $('#unusedResultsWrap').style.display = 'none';
      $('#unusedEmptyMsg').textContent = '✓ Aucune photo inutilisée trouvée — tout ce qui est sur le serveur sert au site.';
      $('#unusedEmptyMsg').style.display = 'block';
      return;
    }
    $('#unusedResultsWrap').style.display = 'block';
    renderUnusedPhotos();
  } catch(e){
    showToast(e.message || 'Erreur réseau pendant la recherche des photos inutilisées.', true);
  } finally {
    btn.disabled = false;
    btn.textContent = originalLabel;
  }
}
$('#scanUnusedBtn').addEventListener('click', scanUnusedPhotos);

function renderUnusedPhotos(){
  const list = $('#unusedList');
  list.innerHTML = '';
  UNUSED_PHOTOS.forEach((item, idx) => {
    const card = document.createElement('div');
    card.style.cssText = 'display:flex; gap:12px; align-items:flex-start; border:1px solid var(--line); border-radius:12px; padding:10px 12px;';
    const backupsNote = item.backups && item.backups.length
      ? `<div class="pm-slot-note is-warn" style="margin-top:6px;">⚠️ ${item.backups.length === 1 ? '1 ancienne sauvegarde sera aussi supprimée' : item.backups.length + ' anciennes sauvegardes seront aussi supprimées'} car elle${item.backups.length > 1 ? 's' : ''} référence${item.backups.length > 1 ? 'nt' : ''} encore cette photo : <strong>${item.backups.map(b => escapeHtml(b)).join(', ')}</strong></div>`
      : '';
    // Le serveur a déjà vérifié le contenu réel du fichier (pas seulement
    // son extension) : si ce n'est pas une image décodable (fichier
    // corrompu, ou format que le navigateur ne sait pas afficher, ex.
    // AVIF/HEIC renommé en .jpg), inutile de faire attendre une miniature
    // qui n'arrivera jamais — on l'affiche tout de suite, avec le motif.
    const invalidNote = item.invalidReason
      ? `<div class="pm-slot-note is-warn" style="margin-top:6px;">⚠️ ${escapeHtml(item.invalidReason)}</div>`
      : '';
    card.innerHTML = `
      <input type="checkbox" style="margin-top:6px; width:18px; height:18px; flex:none;" ${item.selected ? 'checked' : ''}>
      <div data-thumb-wrap style="width:56px; height:56px; border-radius:8px; border:1px solid var(--line); flex:none; background:var(--paper-soft); display:flex; align-items:center; justify-content:center; overflow:hidden; position:relative;">
        ${item.invalidReason
          ? '<span style="font-size:1.4rem;" title="Aperçu indisponible">🚫</span>'
          : '<div class="spinner-sm" data-thumb-spinner></div><img data-thumb src="' + escapeHtml(item.path) + '" alt="" style="width:100%; height:100%; object-fit:cover; display:none;" loading="lazy">'}
      </div>
      <div style="flex:1; min-width:0;">
        <div style="font-weight:600; font-size:.88rem; word-break:break-word;">${escapeHtml(diffImgBase(item.path))}</div>
        <div class="desc" style="margin:2px 0 0;">${escapeHtml(item.path)} — ${formatFileSize(item.size)}</div>
        ${invalidNote}
        ${backupsNote}
      </div>
    `;
    const checkbox = card.querySelector('input[type=checkbox]');
    checkbox.addEventListener('change', () => {
      item.selected = checkbox.checked;
      updateUnusedSummary();
    });
    // La carte doit toujours être ajoutée à la liste, y compris pour les
    // photos signalées invalides par le serveur (invalidReason) — sinon
    // elles disparaissent purement et simplement de l'affichage au lieu
    // de montrer l'icône 🚫 prévue pour ce cas. C'était le bug : l'ajout
    // se faisait après un `return` anticipé qui l'empêchait d'être atteint.
    list.appendChild(card);
    if (item.invalidReason) return; // pas de miniature à câbler pour ce cas
    const thumbWrap = card.querySelector('[data-thumb-wrap]');
    const spinner = card.querySelector('[data-thumb-spinner]');
    const img = card.querySelector('[data-thumb]');
    let thumbSettled = false;
    img.addEventListener('load', () => {
      thumbSettled = true;
      if (spinner) spinner.style.display = 'none';
      img.style.display = 'block';
    });
    img.addEventListener('error', () => {
      thumbSettled = true;
      thumbWrap.innerHTML = '<span style="font-size:1.4rem;" title="Aperçu indisponible">🖼️</span>';
    });
    // Filet de sécurité : si ni load ni error ne se déclenchent après 8s
    // (requête qui traîne, chemin incohérent, blocage réseau...), on ne
    // laisse pas le spinner tourner indéfiniment — on affiche un état
    // explicite avec le chemin exact attendu, pour orienter le diagnostic.
    setTimeout(() => {
      if (thumbSettled) return;
      thumbWrap.innerHTML = '<span style="font-size:1.2rem;" title="' + escapeHtml(item.path) + '">⏱️</span>';
      thumbWrap.title = 'Aperçu indisponible après 8s — vérifie que le fichier existe bien à ce chemin exact sur le serveur : ' + item.path;
    }, 8000);
  });
  updateUnusedSummary();
}

function updateUnusedSummary(){
  const selected = UNUSED_PHOTOS.filter(i => i.selected);
  const backupSet = new Set();
  selected.forEach(i => (i.backups || []).forEach(b => backupSet.add(b)));
  $('#unusedSummary').textContent = UNUSED_PHOTOS.length + ' photo(s) inutilisée(s) trouvée(s)' +
    (selected.length ? ' — ' + selected.length + ' sélectionnée(s)' + (backupSet.size ? ', ' + backupSet.size + ' sauvegarde(s) impactée(s)' : '') : '');
  $('#deleteUnusedBtn').disabled = !selected.length;
}

$('#selectAllUnusedBtn').addEventListener('click', () => {
  UNUSED_PHOTOS.forEach(i => i.selected = true);
  renderUnusedPhotos();
});
$('#selectNoneUnusedBtn').addEventListener('click', () => {
  UNUSED_PHOTOS.forEach(i => i.selected = false);
  renderUnusedPhotos();
});

$('#deleteUnusedBtn').addEventListener('click', async () => {
  const selected = UNUSED_PHOTOS.filter(i => i.selected);
  if (!selected.length) return;

  const backupSet = new Set();
  selected.forEach(i => (i.backups || []).forEach(b => backupSet.add(b)));

  // Réutilise la modale de comparaison comme écran de confirmation
  // détaillé : une section "photos" et, le cas échéant, une section
  // "anciennes sauvegardes", chacune listant précisément ce qui va
  // être supprimé définitivement.
  const changes = [{
    key: 'cleanup-photos',
    title: 'Photos supprimées définitivement',
    rows: selected.map(i => ({ type: 'removed', html: `<strong>${escapeHtml(diffImgBase(i.path))}</strong> (${escapeHtml(i.path)}, ${formatFileSize(i.size)})` }))
  }];
  if (backupSet.size){
    changes.push({
      key: 'cleanup-backups',
      title: 'Anciennes sauvegardes supprimées avec elles',
      rows: Array.from(backupSet).map(b => ({ type: 'removed', html: `<strong>${escapeHtml(b)}</strong> — référence encore une photo supprimée` }))
    });
  }

  const proceed = await showDiffModal({
    icon: '🧹',
    title: 'Supprimer définitivement ces éléments ?',
    subtitle: 'Cette action est irréversible : les fichiers seront supprimés du serveur, pas seulement retirés du site.',
    changes,
    confirmLabel: '🗑 Supprimer définitivement',
    cancelLabel: 'Annuler'
  });
  if (!proceed) return;

  const btn = $('#deleteUnusedBtn');
  const originalLabel = btn.textContent;
  btn.disabled = true;
  btn.textContent = 'Suppression…';
  try {
    const res = await fetchWithTimeout('inc/delete-images.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ paths: selected.map(i => i.path) })
    }, 25000);
    const data = await readJsonSafe(res);
    if (res.status === 401){
      showToast('Session expirée — recharge la page.', true);
      return;
    }
    if (!res.ok || !data || !data.ok){
      showToast((data && data.message) || 'Échec de la suppression.', true);
      return;
    }

    const deletedPhotos = data.deletedPhotos || [];
    const deletedBackups = data.deletedBackups || [];
    const skipped = data.skipped || [];

    const parts = [];
    if (deletedPhotos.length) parts.push(deletedPhotos.length + ' photo(s) supprimée(s)');
    if (deletedBackups.length) parts.push(deletedBackups.length + ' sauvegarde(s) supprimée(s)');
    if (skipped.length) parts.push(skipped.length + ' non supprimée(s)');

    if (skipped.length){
      // Un seul toast consolidé : des showToast() en boucle s'écraseraient
      // les uns les autres et seul le dernier resterait lisible.
      const detail = skipped.map(s => '• ' + diffImgBase(s.path) + ' : ' + s.reason).join('\n');
      showToast((parts.join(', ') || 'Rien n\u2019a été supprimé') + '.\n' + detail, !deletedPhotos.length);
    } else {
      showToast(parts.length ? parts.join(', ') + '.' : 'Rien n\u2019a été supprimé.');
    }

    // Relance une recherche pour refléter l'état réel du serveur.
    await scanUnusedPhotos();
    if (deletedPhotos.length) invalidateServerImages();
    // Des sauvegardes ont pu être supprimées en cascade (elles référençaient
    // encore une photo effacée) — le panneau « Anciennes versions » se
    // remet à jour tout seul si besoin.
    if (deletedBackups.length) invalidateBackups();
  } catch(e){
    showToast(e.message || 'Erreur réseau pendant la suppression.', true);
  } finally {
    btn.disabled = false;
    btn.textContent = originalLabel;
  }
});

/* =====================================================================
   ENVOI DE PHOTOS (inc/upload-image.php) — zone de drop + grille de
   prévisualisation avec renommage avant envoi, un fichier à la fois,
   refuse si un fichier du même nom existe déjà dans images/
   ===================================================================== */
let PU_QUEUE = [];
let PU_ID_SEQ = 0;
let PU_BUSY = false;
let PU_BUCKET = 'images'; // 'images' ou 'galerie' — dossier de destination choisi

/* Aperçu du nettoyage appliqué côté serveur (accents translittérés,
   caractères hors [A-Za-z0-9._-] remplacés par '-') — purement
   indicatif, la vraie règle reste appliquée par upload-image.php. */
function puSanitizePreview(name){
  let base = String(name || '');
  try { base = base.normalize('NFD').replace(/[\u0300-\u036f]/g, ''); } catch(e){ /* tant pis, pas de translittération */ }
  base = base.replace(/[^A-Za-z0-9._-]+/g, '-');
  base = base.replace(/^[-_.]+|[-_.]+$/g, '');
  return base || 'photo';
}

function puAddFiles(fileList){
  const files = filterAcceptableImageFiles(fileList);
  if (!files.length){
    if (fileList && fileList.length) showToast(IMAGE_ACCEPT_HINT, true);
    return;
  }
  const { ok, oversized } = splitOversizedFiles(files);
  warnOversizedFiles(oversized);
  ok.forEach(file => {
    PU_QUEUE.push({
      id: 'pu' + (++PU_ID_SEQ),
      file,
      ext: fileExt(file.name) || 'jpg',
      name: file.name.replace(/\.[^.]+$/, ''),
      url: URL.createObjectURL(file),
      status: 'pending', // pending | loading | done | error
      message: '',
      blocked: false,
      blockedMessage: ''
    });
  });
  renderPhotoUploadQueue();
}

function puRemove(id){
  const idx = PU_QUEUE.findIndex(e => e.id === id);
  if (idx === -1) return;
  URL.revokeObjectURL(PU_QUEUE[idx].url);
  PU_QUEUE.splice(idx, 1);
  renderPhotoUploadQueue();
}

/* Contrôle d'existence AVANT l'envoi : marque chaque photo en attente
   comme « bloquée » si son nom final est déjà pris sur le serveur (dans
   le dossier choisi) ou en double avec une autre photo de la même file.
   Le contrôle définitif reste fait par upload-image.php à l'envoi, mais
   celui-ci évite d'avoir à essayer pour le découvrir. */
function puRevalidateQueue(){
  PU_QUEUE.forEach(entry => {
    if (entry.status === 'done'){ entry.blocked = false; entry.blockedMessage = ''; return; }
    const finalName = puSanitizePreview(entry.name) + '.' + entry.ext;
    const dupInQueue = PU_QUEUE.some(other => other !== entry && other.status !== 'done' &&
      (puSanitizePreview(other.name) + '.' + other.ext).toLowerCase() === finalName.toLowerCase());
    if (dupInQueue){
      entry.blocked = true;
      entry.blockedMessage = 'Deux photos de la file ont le même nom final — renomme l\u2019une des deux.';
    } else if (isNameTakenSync(PU_BUCKET, finalName)){
      entry.blocked = true;
      entry.blockedMessage = 'Ce nom est déjà pris dans ' + (PU_BUCKET === 'galerie' ? 'la galerie' : 'images/') + ' — renomme cette photo.';
    } else {
      entry.blocked = false;
      entry.blockedMessage = '';
    }
  });
}

function renderPhotoUploadQueue(){
  puRevalidateQueue();
  const grid = $('#photoUploadGrid');
  const emptyHint = $('#photoUploadEmptyHint');
  const sendBtn = $('#photoUploadBtn');
  grid.innerHTML = '';
  emptyHint.style.display = PU_QUEUE.length ? 'none' : 'block';
  sendBtn.disabled = PU_BUSY || !PU_QUEUE.some(e => e.status !== 'done') || PU_QUEUE.some(e => e.status === 'pending' && e.blocked);

  PU_QUEUE.forEach(entry => {
    const card = document.createElement('div');
    card.className = 'pu-card' +
      (entry.status === 'done' ? ' is-done' : entry.status === 'error' ? ' is-error' : entry.status === 'loading' ? ' is-uploading' : (entry.blocked ? ' is-blocked' : ''));
    card.dataset.id = entry.id;

    const finalName = puSanitizePreview(entry.name) + '.' + entry.ext;
    const nameChanged = finalName !== (entry.name + '.' + entry.ext);
    const canEdit = entry.status === 'pending' && !PU_BUSY;
    const showRemove = (entry.status === 'pending' || entry.status === 'error') && !PU_BUSY;
    const statusIcon = entry.status === 'loading' ? '<div class="spinner-sm"></div>'
      : entry.status === 'done' ? '✓' : entry.status === 'error' ? '❌' : '';
    const msgText = (entry.status === 'pending' && entry.blocked) ? entry.blockedMessage : entry.message;

    card.innerHTML = `
      <div class="pu-card-thumb-wrap">
        <img class="pu-card-thumb" src="${entry.url}" alt="" onerror="useImgFallback(this)">
        ${showRemove ? `<button type="button" class="pu-card-remove" data-remove="${entry.id}" aria-label="Retirer cette photo">✕</button>` : ''}
        ${statusIcon ? `<div class="pu-card-status">${statusIcon}</div>` : ''}
      </div>
      <div class="pu-card-body">
        <div class="pu-card-name-row">
          <input type="text" class="pu-card-name-input" data-rename="${entry.id}" value="${escapeHtml(entry.name)}" ${canEdit ? '' : 'disabled'} aria-label="Renommer cette photo avant envoi">
          <span class="pu-card-ext">.${escapeHtml(entry.ext)}</span>
        </div>
        <div class="pu-card-meta">${formatFileSize(entry.file.size)}</div>
        <div class="pu-card-preview-name" style="${nameChanged ? '' : 'display:none;'}">sera enregistrée : <strong>${escapeHtml(finalName)}</strong></div>
        <div class="pu-card-msg" style="${msgText ? '' : 'display:none;'}">${escapeHtml(msgText || '')}</div>
      </div>
    `;
    grid.appendChild(card);
  });

  grid.querySelectorAll('[data-remove]').forEach(btn => {
    btn.addEventListener('click', () => puRemove(btn.dataset.remove));
  });
  // Mise à jour ciblée (pas de re-rendu complet) pour ne pas perdre le
  // focus/curseur de saisie pendant que l'admin tape le nouveau nom.
  grid.querySelectorAll('[data-rename]').forEach(input => {
    input.addEventListener('input', () => {
      const entry = PU_QUEUE.find(e => e.id === input.dataset.rename);
      if (!entry) return;
      entry.name = input.value;
      puRevalidateQueue();
      const card = input.closest('.pu-card');
      const previewEl = card.querySelector('.pu-card-preview-name');
      const finalName = puSanitizePreview(entry.name) + '.' + entry.ext;
      const changed = finalName !== (entry.name + '.' + entry.ext);
      previewEl.innerHTML = 'sera enregistrée : <strong>' + escapeHtml(finalName) + '</strong>';
      previewEl.style.display = changed ? '' : 'none';
      card.classList.toggle('is-blocked', entry.status === 'pending' && !!entry.blocked);
      const msgEl = card.querySelector('.pu-card-msg');
      const msgText = (entry.status === 'pending' && entry.blocked) ? entry.blockedMessage : entry.message;
      msgEl.textContent = msgText;
      msgEl.style.display = msgText ? '' : 'none';
      $('#photoUploadBtn').disabled = PU_BUSY || !PU_QUEUE.some(e => e.status !== 'done') || PU_QUEUE.some(e => e.status === 'pending' && e.blocked);
    });
  });
}

async function puUploadEntry(entry, confirmPassword){
  entry.status = 'loading';
  entry.message = 'envoi en cours…';
  renderPhotoUploadQueue();

  const finalName = puSanitizePreview(entry.name) + '.' + entry.ext;
  const form = new FormData();
  form.append('photo', entry.file, finalName);
  form.append('bucket', PU_BUCKET);
  form.append('confirm_password', confirmPassword);
  try {
    // Timeout généreux (90s) : un envoi peut être long sur une
    // connexion mobile ou pour une photo volumineuse.
    const res = await fetchWithTimeout('inc/upload-image.php', {
      method: 'POST',
      credentials: 'same-origin',
      body: form
    }, 90000);
    const data = await readJsonSafe(res);

    if (res.status === 401){
      entry.status = 'error';
      entry.message = (data && data.message) || 'Session expirée — reconnecte-toi.';
    } else if (res.status === 403){
      entry.status = 'error';
      entry.message = (data && data.message) || 'Mot de passe de confirmation incorrect — envoi refusé.';
    } else if (!res.ok || !data || !data.ok){
      entry.status = 'error';
      entry.message = (data && data.message) || ('le serveur a répondu avec une erreur (' + res.status + ')');
    } else {
      const destLabel = PU_BUCKET === 'galerie' ? 'images/galerie/' : 'images/';
      entry.status = 'done';
      entry.message = data.renamed
        ? 'envoyée sous le nom ' + data.filename + ' dans ' + destLabel + ' (nom nettoyé automatiquement)'
        : 'envoyée dans ' + destLabel;
    }
  } catch(e){
    entry.status = 'error';
    entry.message = e.message || 'Impossible de contacter le serveur.';
  }
  renderPhotoUploadQueue();
}

/* --------- Zone de drop : clic pour parcourir + glisser-déposer --------- */
const photoDropZone = $('#photoDropZone');
const photoUploadInput = $('#photoUploadInput');
photoDropZone.addEventListener('click', () => photoUploadInput.click());
photoDropZone.addEventListener('keydown', (e) => {
  if (e.key === 'Enter' || e.key === ' '){ e.preventDefault(); photoUploadInput.click(); }
});
photoUploadInput.addEventListener('change', () => {
  puAddFiles(photoUploadInput.files);
  photoUploadInput.value = ''; // permet de re-choisir le même fichier plus tard
});
['dragenter', 'dragover'].forEach(evt => {
  photoDropZone.addEventListener(evt, (e) => {
    e.preventDefault(); e.stopPropagation();
    photoDropZone.classList.add('is-drag');
  });
});
['dragleave', 'dragend'].forEach(evt => {
  photoDropZone.addEventListener(evt, (e) => {
    e.preventDefault(); e.stopPropagation();
    photoDropZone.classList.remove('is-drag');
  });
});
photoDropZone.addEventListener('drop', (e) => {
  e.preventDefault(); e.stopPropagation();
  photoDropZone.classList.remove('is-drag');
  puAddFiles(e.dataTransfer && e.dataTransfer.files);
});
// Empêche le navigateur d'ouvrir l'image en grand s'il y a un raté hors zone.
['dragover', 'drop'].forEach(evt => {
  document.addEventListener(evt, (e) => { if (e.target !== photoDropZone && !photoDropZone.contains(e.target)) e.preventDefault(); });
});

document.querySelectorAll('input[name="puBucket"]').forEach(radio => {
  radio.addEventListener('change', () => {
    if (!radio.checked) return;
    PU_BUCKET = radio.value === 'galerie' ? 'galerie' : 'images';
    renderPhotoUploadQueue();
  });
});

renderPhotoUploadQueue();

$('#photoUploadBtn').addEventListener('click', async () => {
  const pending = PU_QUEUE.filter(e => e.status !== 'done');
  if (!pending.length){
    showToast('Choisis au moins une photo avant d\u2019envoyer.', true);
    return;
  }

  // Contrôle d'existence final avec une liste serveur fraîche, juste
  // avant de demander le mot de passe — le cache local a pu devenir
  // obsolète depuis le dernier chargement.
  await loadServerImages(true);
  renderPhotoUploadQueue();
  if (PU_QUEUE.some(e => e.status === 'pending' && e.blocked)){
    showToast('Certains noms sont déjà pris sur le serveur — renomme-les avant d\u2019envoyer.', true);
    return;
  }

  // Même sécurité que pour la publication : mot de passe redemandé à
  // chaque envoi, même si l'admin est déjà connectée.
  const pwResult = await askPublishPassword({
    icon: '📷',
    title: 'Confirme l\u2019envoi',
    message: pending.length > 1
      ? 'Pour envoyer ces ' + pending.length + ' photos sur le serveur, ressaisis le mot de passe admin.'
      : 'Pour envoyer cette photo sur le serveur, ressaisis le mot de passe admin.',
    okLabel: '⬆ Envoyer'
  });
  if (!pwResult.ok){
    showToast('Envoi annulé — aucune photo n\u2019a été transmise.', true);
    return;
  }

  const btn = $('#photoUploadBtn');
  const originalLabel = btn.textContent;
  PU_BUSY = true;
  renderPhotoUploadQueue();

  for (const entry of pending){
    btn.textContent = 'Envoi de ' + entry.name + '.' + entry.ext + '…';
    await puUploadEntry(entry, pwResult.password);
  }

  btn.textContent = originalLabel;
  PU_BUSY = false;
  renderPhotoUploadQueue();
  const anyError = PU_QUEUE.some(e => e.status === 'error');
  showToast(
    anyError ? 'Envoi terminé avec des erreurs — voir le détail sur les photos concernées.' : 'Envoi des photos terminé.',
    anyError
  );
  // Une photo tout juste envoyée mais pas encore référencée dans un
  // champ apparaîtrait comme "inutilisée" — le panneau de nettoyage se
  // remet à jour tout seul s'il y a eu au moins un envoi réussi.
  if (pending.some(e => e.status === 'done')){
    invalidateUnusedPhotos();
    invalidateServerImages();
  }
});

/* =====================================================================
   AJOUT MULTIPLE À LA GALERIE (inc/upload-image.php, target_path)
   Zone de drop dédiée dans le panneau « Galerie photo » : envoie chaque
   photo directement dans images/galerie/ et l'ajoute automatiquement à
   CONTENT.galerie une fois envoyée, sans ressaisie manuelle du nom.
   Même contrôle d'existence avant envoi que le panneau libre.
   ===================================================================== */
let GAL_QUEUE = [];
let GAL_ID_SEQ = 0;
let GAL_BUSY = false;

function galAddFiles(fileList){
  const files = filterAcceptableImageFiles(fileList);
  if (!files.length){
    if (fileList && fileList.length) showToast(IMAGE_ACCEPT_HINT, true);
    return;
  }
  const { ok, oversized } = splitOversizedFiles(files);
  warnOversizedFiles(oversized);
  ok.forEach(file => {
    GAL_QUEUE.push({
      id: 'gal' + (++GAL_ID_SEQ),
      file,
      ext: fileExt(file.name) || 'jpg',
      name: file.name.replace(/\.[^.]+$/, ''),
      url: URL.createObjectURL(file),
      status: 'pending', // pending | loading | done | error
      message: '',
      blocked: false,
      blockedMessage: '',
      finalPath: null
    });
  });
  renderGalerieUploadQueue();
}

function galRemove(id){
  const idx = GAL_QUEUE.findIndex(e => e.id === id);
  if (idx === -1) return;
  URL.revokeObjectURL(GAL_QUEUE[idx].url);
  GAL_QUEUE.splice(idx, 1);
  renderGalerieUploadQueue();
}

function galRevalidateQueue(){
  GAL_QUEUE.forEach(entry => {
    if (entry.status === 'done'){ entry.blocked = false; entry.blockedMessage = ''; return; }
    const finalName = puSanitizePreview(entry.name) + '.' + entry.ext;
    const dupInQueue = GAL_QUEUE.some(other => other !== entry && other.status !== 'done' &&
      (puSanitizePreview(other.name) + '.' + other.ext).toLowerCase() === finalName.toLowerCase());
    if (dupInQueue){
      entry.blocked = true;
      entry.blockedMessage = 'Deux photos de la file ont le même nom final — renomme l\u2019une des deux.';
    } else if (isNameTakenSync('galerie', finalName)){
      entry.blocked = true;
      entry.blockedMessage = 'Ce nom est déjà pris dans la galerie — renomme cette photo.';
    } else {
      entry.blocked = false;
      entry.blockedMessage = '';
    }
  });
}

function renderGalerieUploadQueue(){
  galRevalidateQueue();
  const grid = $('#galerieUploadGrid');
  const emptyHint = $('#galerieUploadEmptyHint');
  const sendBtn = $('#galerieUploadBtn');
  grid.innerHTML = '';
  emptyHint.style.display = GAL_QUEUE.length ? 'none' : 'block';
  sendBtn.style.display = GAL_QUEUE.length ? '' : 'none';
  sendBtn.disabled = GAL_BUSY || !GAL_QUEUE.some(e => e.status !== 'done') || GAL_QUEUE.some(e => e.status === 'pending' && e.blocked);

  GAL_QUEUE.forEach(entry => {
    const card = document.createElement('div');
    card.className = 'pu-card' +
      (entry.status === 'done' ? ' is-done' : entry.status === 'error' ? ' is-error' : entry.status === 'loading' ? ' is-uploading' : (entry.blocked ? ' is-blocked' : ''));
    card.dataset.id = entry.id;

    const finalName = puSanitizePreview(entry.name) + '.' + entry.ext;
    const nameChanged = finalName !== (entry.name + '.' + entry.ext);
    const canEdit = entry.status === 'pending' && !GAL_BUSY;
    const showRemove = (entry.status === 'pending' || entry.status === 'error') && !GAL_BUSY;
    const statusIcon = entry.status === 'loading' ? '<div class="spinner-sm"></div>'
      : entry.status === 'done' ? '✓' : entry.status === 'error' ? '❌' : '';
    const msgText = (entry.status === 'pending' && entry.blocked) ? entry.blockedMessage : entry.message;

    card.innerHTML = `
      <div class="pu-card-thumb-wrap">
        <img class="pu-card-thumb" src="${entry.url}" alt="" onerror="useImgFallback(this)">
        ${showRemove ? `<button type="button" class="pu-card-remove" data-gal-remove="${entry.id}" aria-label="Retirer cette photo">✕</button>` : ''}
        ${statusIcon ? `<div class="pu-card-status">${statusIcon}</div>` : ''}
      </div>
      <div class="pu-card-body">
        <div class="pu-card-name-row">
          <input type="text" class="pu-card-name-input" data-gal-rename="${entry.id}" value="${escapeHtml(entry.name)}" ${canEdit ? '' : 'disabled'} aria-label="Renommer cette photo avant envoi">
          <span class="pu-card-ext">.${escapeHtml(entry.ext)}</span>
        </div>
        <div class="pu-card-meta">${formatFileSize(entry.file.size)}</div>
        <div class="pu-card-preview-name" style="${nameChanged ? '' : 'display:none;'}">sera enregistrée : <strong>${escapeHtml(finalName)}</strong></div>
        <div class="pu-card-msg" style="${msgText ? '' : 'display:none;'}">${escapeHtml(msgText || '')}</div>
      </div>
    `;
    grid.appendChild(card);
  });

  grid.querySelectorAll('[data-gal-remove]').forEach(btn => {
    btn.addEventListener('click', () => galRemove(btn.dataset.galRemove));
  });
  grid.querySelectorAll('[data-gal-rename]').forEach(input => {
    input.addEventListener('input', () => {
      const entry = GAL_QUEUE.find(e => e.id === input.dataset.galRename);
      if (!entry) return;
      entry.name = input.value;
      galRevalidateQueue();
      const card = input.closest('.pu-card');
      const previewEl = card.querySelector('.pu-card-preview-name');
      const finalName = puSanitizePreview(entry.name) + '.' + entry.ext;
      const changed = finalName !== (entry.name + '.' + entry.ext);
      previewEl.innerHTML = 'sera enregistrée : <strong>' + escapeHtml(finalName) + '</strong>';
      previewEl.style.display = changed ? '' : 'none';
      card.classList.toggle('is-blocked', entry.status === 'pending' && !!entry.blocked);
      const msgEl = card.querySelector('.pu-card-msg');
      const msgText = (entry.status === 'pending' && entry.blocked) ? entry.blockedMessage : entry.message;
      msgEl.textContent = msgText;
      msgEl.style.display = msgText ? '' : 'none';
      $('#galerieUploadBtn').disabled = GAL_BUSY || !GAL_QUEUE.some(e => e.status !== 'done') || GAL_QUEUE.some(e => e.status === 'pending' && e.blocked);
    });
  });
}

async function galUploadEntry(entry, confirmPassword){
  entry.status = 'loading';
  entry.message = 'envoi en cours…';
  renderGalerieUploadQueue();

  const finalName = puSanitizePreview(entry.name) + '.' + entry.ext;
  const targetPath = 'images/galerie/' + finalName;
  const form = new FormData();
  form.append('photo', entry.file, finalName);
  form.append('target_path', targetPath);
  form.append('confirm_password', confirmPassword);
  try {
    const res = await fetchWithTimeout('inc/upload-image.php', { method: 'POST', credentials: 'same-origin', body: form }, 90000);
    const data = await readJsonSafe(res);
    if (res.status === 401){
      entry.status = 'error';
      entry.message = (data && data.message) || 'Session expirée — reconnecte-toi.';
    } else if (res.status === 403){
      entry.status = 'error';
      entry.message = (data && data.message) || 'Mot de passe de confirmation incorrect — envoi refusé.';
    } else if (!res.ok || !data || !data.ok){
      entry.status = 'error';
      entry.message = (data && data.message) || ('le serveur a répondu avec une erreur (' + res.status + ')');
    } else {
      entry.status = 'done';
      entry.message = 'ajoutée à la galerie';
      entry.finalPath = data.path || targetPath;
    }
  } catch(e){
    entry.status = 'error';
    entry.message = e.message || 'Impossible de contacter le serveur.';
  }
  renderGalerieUploadQueue();
}

const galerieDropZone = $('#galerieDropZone');
const galerieDropInput = $('#galerieDropInput');
galerieDropZone.addEventListener('click', () => galerieDropInput.click());
galerieDropZone.addEventListener('keydown', (e) => {
  if (e.key === 'Enter' || e.key === ' '){ e.preventDefault(); galerieDropInput.click(); }
});
galerieDropInput.addEventListener('change', () => {
  galAddFiles(galerieDropInput.files);
  galerieDropInput.value = '';
});
['dragenter', 'dragover'].forEach(evt => {
  galerieDropZone.addEventListener(evt, (e) => {
    e.preventDefault(); e.stopPropagation();
    galerieDropZone.classList.add('is-drag');
  });
});
['dragleave', 'dragend'].forEach(evt => {
  galerieDropZone.addEventListener(evt, (e) => {
    e.preventDefault(); e.stopPropagation();
    galerieDropZone.classList.remove('is-drag');
  });
});
galerieDropZone.addEventListener('drop', (e) => {
  e.preventDefault(); e.stopPropagation();
  galerieDropZone.classList.remove('is-drag');
  galAddFiles(e.dataTransfer && e.dataTransfer.files);
});
['dragover', 'drop'].forEach(evt => {
  document.addEventListener(evt, (e) => { if (e.target !== galerieDropZone && !galerieDropZone.contains(e.target)) e.preventDefault(); });
});

renderGalerieUploadQueue();

$('#galerieUploadBtn').addEventListener('click', async () => {
  const pending = GAL_QUEUE.filter(e => e.status !== 'done');
  if (!pending.length){
    showToast('Choisis au moins une photo avant d\u2019envoyer.', true);
    return;
  }

  await loadServerImages(true);
  renderGalerieUploadQueue();
  if (GAL_QUEUE.some(e => e.status === 'pending' && e.blocked)){
    showToast('Certains noms sont déjà pris sur le serveur — renomme-les avant d\u2019envoyer.', true);
    return;
  }

  const pwResult = await askPublishPassword({
    icon: '📷',
    title: 'Confirme l\u2019envoi',
    message: pending.length > 1
      ? 'Pour envoyer ces ' + pending.length + ' photos dans la galerie, ressaisis le mot de passe admin.'
      : 'Pour envoyer cette photo dans la galerie, ressaisis le mot de passe admin.',
    okLabel: '⬆ Envoyer'
  });
  if (!pwResult.ok){
    showToast('Envoi annulé — aucune photo n\u2019a été ajoutée.', true);
    return;
  }

  const btn = $('#galerieUploadBtn');
  const originalLabel = btn.textContent;
  GAL_BUSY = true;
  renderGalerieUploadQueue();

  for (const entry of pending){
    btn.textContent = 'Envoi de ' + entry.name + '.' + entry.ext + '…';
    await galUploadEntry(entry, pwResult.password);
  }

  btn.textContent = originalLabel;
  GAL_BUSY = false;

  const justAdded = pending.filter(e => e.status === 'done');
  if (justAdded.length){
    justAdded.forEach(e => { CONTENT.galerie.push({ src: e.finalPath, alt: '' }); });
    renderGalerie();
    commitChange(justAdded.length > 1 ? 'Ajout de ' + justAdded.length + ' photos à la galerie' : 'Ajout d\u2019une photo à la galerie');
    invalidateUnusedPhotos();
    invalidateServerImages();
    // Retire du panneau les photos réussies (déjà intégrées à la galerie
    // ci-dessus) ; celles en erreur restent affichées pour un nouvel essai.
    justAdded.forEach(e => URL.revokeObjectURL(e.url));
    GAL_QUEUE = GAL_QUEUE.filter(e => e.status !== 'done');
  }
  renderGalerieUploadQueue();

  const anyError = pending.some(e => e.status === 'error');
  showToast(
    anyError ? 'Envoi terminé avec des erreurs — voir le détail sur les photos concernées.' : 'Photo(s) ajoutée(s) à la galerie.',
    anyError
  );
});

/* =====================================================================
   PANNEAUX PLIABLES — animation d'accordéon fluide (hauteur mesurée en
   JS puis transition CSS sur max-height/opacity). Une fois l'ouverture
   terminée, max-height repasse à « none » pour laisser le contenu
   grandir librement (ex. ajout d'un cours) sans être coupé.
   ===================================================================== */
/* Déclenche le chargement automatique d'un panneau "lazy" (anciennes
   versions / photos inutilisées) une fois son ouverture terminée : au
   tout premier affichage, ou si une action ailleurs dans l'admin a
   entre-temps rendu la dernière liste obsolète (voir invalidateBackups
   / invalidateUnusedPhotos). Ne fait rien pour les autres panneaux, ni
   si les données affichées sont déjà à jour — pas de requête inutile
   à chaque ouverture/fermeture répétée. */
function handleLazyPanelOpen(panel){
  const kind = panel.dataset.lazyPanel;
  if (kind === 'backups' && (!BACKUPS_LOADED || BACKUPS_STALE)) loadBackupsList();
  else if (kind === 'unused' && (!UNUSED_SCANNED || UNUSED_STALE)) scanUnusedPhotos();
}
function togglePanel(panel){
  const body = panel.querySelector('.panel-body');
  if (!body) return;
  const opening = !panel.classList.contains('open');
  if (opening){
    panel.classList.add('open');
    body.style.display = 'block';
    const target = body.scrollHeight;
    body.style.maxHeight = '0px';
    body.style.opacity = '0';
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        body.style.maxHeight = target + 'px';
        body.style.opacity = '1';
      });
    });
    const onEnd = (e) => {
      if (e.propertyName !== 'max-height') return;
      body.style.maxHeight = 'none';
      body.removeEventListener('transitionend', onEnd);
      handleLazyPanelOpen(panel);
    };
    body.addEventListener('transitionend', onEnd);
  } else {
    const current = body.scrollHeight;
    body.style.maxHeight = current + 'px';
    body.style.opacity = '1';
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        body.style.maxHeight = '0px';
        body.style.opacity = '0';
      });
    });
    const onEnd = (e) => {
      if (e.propertyName !== 'max-height') return;
      panel.classList.remove('open');
      body.style.display = 'none';
      body.removeEventListener('transitionend', onEnd);
    };
    body.addEventListener('transitionend', onEnd);
  }
}
/* État initial (sans animation) : les panneaux marqués "open" dans le
   HTML doivent être visibles dès le chargement, les autres masqués. */
function initPanelsState(){
  $$('[data-panel]').forEach(panel => {
    const body = panel.querySelector('.panel-body');
    if (!body) return;
    if (panel.classList.contains('open')){
      body.style.display = 'block';
      body.style.maxHeight = 'none';
      body.style.opacity = '1';
    } else {
      body.style.display = 'none';
      body.style.maxHeight = '0px';
      body.style.opacity = '0';
    }
  });
}
initPanelsState();
$$('[data-toggle]').forEach(head => {
  head.addEventListener('click', () => togglePanel(head.closest('[data-panel]')));
});

/* Boutons « Tout déplier » / « Tout replier » — ne touche que les
   panneaux dont l'état diffère de la cible, pour ne pas relancer
   l'animation d'ouverture/fermeture sur ceux déjà dans le bon état. */
function setAllPanels(shouldOpen){
  $$('[data-panel]').forEach(panel => {
    const isOpen = panel.classList.contains('open');
    if (isOpen !== shouldOpen) togglePanel(panel);
  });
}
$('#expandAllBtn').addEventListener('click', () => setAllPanels(true));
$('#collapseAllBtn').addEventListener('click', () => setAllPanels(false));

/* =====================================================================
   GUIDE — mise en forme des blocs "content.json" en mini fenêtres de
   code (barre à pastilles + coloration syntaxique légère), pour
   remplacer le pavé de texte brut qui débordait horizontalement.
   Purement cosmétique : n'a aucune influence sur CONTENT.
   ===================================================================== */
function highlightGuideJson(text){
  let out = text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
  // Commentaires « ← … » ajoutés à titre pédagogique dans certains exemples
  out = out.split('\n').map(line => {
    const idx = line.indexOf('←');
    return idx === -1 ? line : line.slice(0, idx) + '<span class="gj-comment">' + line.slice(idx) + '</span>';
  }).join('\n');
  // Clés ("mot":) puis valeurs texte ( : "mot") puis booléens
  out = out.replace(/("(?:[^"\\]|\\.)*")(\s*:)/g, '<span class="gj-key">$1</span>$2');
  out = out.replace(/:(\s*)("(?:[^"\\]|\\.)*")/g, ':$1<span class="gj-string">$2</span>');
  out = out.replace(/\btrue\b/g, '<span class="gj-bool-true">true</span>');
  out = out.replace(/\bfalse\b/g, '<span class="gj-bool-false">false</span>');
  return out;
}
function initGuideJsonWindows(){
  $$('.guide-json').forEach(el => {
    if (el.closest('.guide-json-window')) return; // déjà transformé
    const raw = el.textContent;
    const win = document.createElement('div');
    win.className = 'guide-json-window';
    const bar = document.createElement('div');
    bar.className = 'guide-json-bar';
    bar.innerHTML =
      '<span class="guide-json-dot" style="background:#ec6a5e"></span>' +
      '<span class="guide-json-dot" style="background:#f4bf4f"></span>' +
      '<span class="guide-json-dot" style="background:#61c454"></span>' +
      '<span class="guide-json-label">content.json</span>';
    el.parentNode.insertBefore(win, el);
    win.appendChild(bar);
    win.appendChild(el);
    el.innerHTML = highlightGuideJson(raw);
  });
}
initGuideJsonWindows();

/* =====================================================================
   POLISH VISUEL — ombre de l'en-tête au scroll, entrée en douceur du
   contenu principal et masquage progressif de l'écran de chargement.
   ===================================================================== */
window.addEventListener('scroll', () => {
  $('.admin-header').classList.toggle('is-scrolled', window.scrollY > 4);
}, { passive:true });

/* Onde tactile au clic sur tous les boutons (.btn) — délégué sur le
   document pour couvrir aussi les boutons créés dynamiquement (modales,
   cartes, etc.). Ignorée si l'utilisateur préfère moins d'animations,
   ou si le bouton est désactivé. Le nœud se retire lui-même après son
   animation (avec un filet de sécurité au cas où 'animationend' ne se
   déclencherait pas, pour ne jamais laisser de nœuds orphelins). */
(function initButtonRipple(){
  const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (reduceMotion) return;
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn');
    if (!btn || btn.disabled) return;
    const rect = btn.getBoundingClientRect();
    if (!rect.width || !rect.height) return;
    const size = Math.max(rect.width, rect.height) * 1.3;
    const originX = e.detail === 0 ? rect.width / 2 : (e.clientX - rect.left);
    const originY = e.detail === 0 ? rect.height / 2 : (e.clientY - rect.top);
    const ripple = document.createElement('span');
    ripple.className = 'btn-ripple';
    ripple.style.width = ripple.style.height = size + 'px';
    ripple.style.left = (originX - size / 2) + 'px';
    ripple.style.top = (originY - size / 2) + 'px';
    btn.appendChild(ripple);
    const cleanup = () => ripple.remove();
    ripple.addEventListener('animationend', cleanup, { once:true });
    setTimeout(cleanup, 700); // filet de sécurité
  }, true);
})();

init();
</script>
</body>
</html>
