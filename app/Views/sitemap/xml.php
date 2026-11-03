<?= '<?xml version="1.0" encoding="UTF-8"?>' ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($staticPages as $page): ?>
    <url>
        <loc><?= site_url($page['url']) ?></loc>
        <changefreq><?= $page['changefreq'] ?></changefreq>
        <priority><?= $page['priority'] ?></priority>
    </url>
<?php endforeach; ?>
<?php foreach ($properties as $property):
    $rawDate   = $property['updated_at'] ?? '';
    $timestamp = (! empty($rawDate) && strpos($rawDate, '0000-00-00') !== 0) ? strtotime($rawDate) : false;
    if ($timestamp !== false && (int) date('Y', $timestamp) < 2000) {
        $timestamp = false;
    }
?>
    <url>
        <loc><?= site_url('property/' . $property['pid']) ?></loc>
<?php if ($timestamp !== false): ?>
        <lastmod><?= date('Y-m-d', $timestamp) ?></lastmod>
<?php endif; ?>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
<?php endforeach; ?>
</urlset>
