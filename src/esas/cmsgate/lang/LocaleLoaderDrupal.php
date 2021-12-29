<?php
/**
 * Created by PhpStorm.
 * User: nikit
 * Date: 27.09.2018
 * Time: 13:09
 */

namespace esas\cmsgate\lang;

class LocaleLoaderDrupal extends LocaleLoaderCms
{

    public function getLocale()
    {
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        return $language . "_" . strtoupper($language);
    }


    public function getCmsVocabularyDir()
    {
        return dirname(__FILE__);
    }
}