  <footer>
    <p>© 2026 <span>Uber-Cueillette</span> — Institut Supérieur de Gestion de Tunis<br> Feriel Bouazizi _ Yosr Ferjani</p>
  </footer>

  <?php if (!empty($pageScript)): ?>
    <script src="<?= ($basePath ?? '') . 'js/' . htmlspecialchars($pageScript) ?>"></script>
  <?php endif; ?>
</body>
</html>
