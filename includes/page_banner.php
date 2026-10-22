<?php
// Shared by the home banner and the main storefront pages.
$bannerTitle = $banner['title'] ?? '';
$bannerDescription = $banner['description'] ?? '';
$bannerImage = $banner['image'] ?? 'banners/banner1.jpg';
$bannerInsideContainer = $banner['inside_container'] ?? false;
?>
<?php if (!$bannerInsideContainer): ?><div class="container banner-shell"><?php endif; ?>
<section class="shop-banner<?= !empty($banner['home']) ? ' shop-banner-home' : '' ?>" aria-label="<?= sanitize(str_replace("\n", ' ', $bannerTitle)) ?>">
    <div class="shop-banner-copy">
        <h1><?= nl2br(sanitize($bannerTitle)) ?></h1>
        <?php if ($bannerDescription): ?><p><?= sanitize($bannerDescription) ?></p><?php endif; ?>
        <?php if (!empty($banner['link'])): ?><a class="btn btn-primary" href="<?= sanitize($banner['link']) ?>"><?= sanitize($banner['button'] ?? 'Khám phá') ?><i class="fas fa-arrow-right ms-2" aria-hidden="true"></i></a><?php endif; ?>
    </div>
    <div class="shop-banner-visual"><img src="<?= BASE_URL ?>/assets/images/<?= sanitize($bannerImage) ?>" alt="<?= sanitize($banner['alt'] ?? str_replace("\n", ' ', $bannerTitle)) ?>" fetchpriority="high"></div>
</section>
<?php if (!$bannerInsideContainer): ?></div><?php endif; ?>
