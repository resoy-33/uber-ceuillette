<?php
$pageTitle = "Accueil";
require_once 'header.php';


if (isset($_SESSION['user'])) {
    $role = $_SESSION['role'];
    header("Location: $role/dashboard.php");
    exit;
}
?>

<section class="hero">
  
  <h1> Uber-Cueillette</h1>
  <p>La plateforme qui met en relation agriculteurs et ouvriers pour les saisons de récolte d'olives, d'agrumes, de tomates et bien plus.</p>
  <div class="hero-btns">
    <a href="register_agriculteur.php" class="btn btn-earth">Je suis Agriculteur</a>
    <a href="register_ouvrier.php" class="btn btn-secondary" style="color:#fff;border-color:#fff;">Je suis Ouvrier</a>
  </div>
</section>

<div class="container">
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.5rem;margin:2rem 0;">
    <div class="card" style="text-align:center;">
      <div style="font-size:2.5rem;margin-bottom:0.8rem;"></div>
      <h3 style="font-family:'Playfair Display',serif;color:var(--olive-dark);margin-bottom:0.5rem;">Publiez vos offres</h3>
      <p style="color:var(--text-light);font-size:0.9rem;">Les agriculteurs créent des offres de récolte avec tous les détails : fruit, dates, lieu et rémunération.</p>
    </div>
    <div class="card" style="text-align:center;">
      <div style="font-size:2.5rem;margin-bottom:0.8rem;"></div>
      <h3 style="font-family:'Playfair Display',serif;color:var(--olive-dark);margin-bottom:0.5rem;">Trouvez du travail</h3>
      <p style="color:var(--text-light);font-size:0.9rem;">Les ouvriers consultent les offres disponibles, filtrent par fruit ou gouvernorat et postulent en un clic.</p>
    </div>
    <div class="card" style="text-align:center;">
      <div style="font-size:2.5rem;margin-bottom:0.8rem;"></div>
      <h3 style="font-family:'Playfair Display',serif;color:var(--olive-dark);margin-bottom:0.5rem;">Système de notation</h3>
      <p style="color:var(--text-light);font-size:0.9rem;">Après chaque récolte, les agriculteurs évaluent les ouvriers pour construire une réputation de confiance.</p>
    </div>
  </div>
</div>

<?php require_once 'footer.php'; ?>
