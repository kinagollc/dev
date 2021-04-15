<?php
/**
 * Update Controller
 *
 */
class SitemapController extends CController
{
    public function actionIndex()
    {
    header('Content-type: application/xml; charset=utf-8');
    $data=Yii::app()->functions->getAllMerchant();
    foreach($data as $new){
      $set = $new;
    }
    foreach($set as $tst)
    {
      $val .= " <url>
      <loc>".Yii::app()->getBaseUrl(true)."/menu-".$tst['restaurant_slug']."</loc>
      <lastmod>".gmdate('c', strtotime($tst['date_modified']))."</lastmod>
      <changefreq>monthly</changefreq>
      <priority>0.9</priority>
 </url>
";
    }
    $output = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
    echo $output;
    echo $val;
    echo '</urlset>';
  }
}
?>