<?php
$isPageDir = str_contains($_SERVER['PHP_SELF'], '/pages/');
$rootPrefix = $isPageDir ? '../' : '';
?>
<nav class="navbar navbar-expand-lg couture-nav sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $rootPrefix ?>index.php">
            <span class="brand-mark">CC</span>
            <span>
                <span class="brand-title">Closet Couture</span>
                <small class="brand-subtitle d-block">Smart Wardrobe Organizer</small>
            </span>
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item"><a class="nav-link" href="<?= $rootPrefix ?>index.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $rootPrefix ?>pages/wardrobe.php">Wardrobe</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $rootPrefix ?>pages/recommendation.php">Recommendations</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $rootPrefix ?>pages/wear_history.php">Wear History</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $rootPrefix ?>pages/insights.php">Insights</a></li>
            </ul>
        </div>
    </div>
</nav>
