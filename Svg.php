<?php

class Signia_ImageResize_Svg extends Signia_ImageResize_Abstract
{
   function getResizedImage()
   {
      if (file_exists($this->srcImageName)) {
         if (copy($this->srcImageName, $this->destImageName)) {
            return true; // Hago esto en vez del return directo de la función porque en Signia/Cms/File.php hay "if ($result !== true)" en vez de "if (!$result)"
         }else{
            return false;
         }
      }

      return $this->error_message;
   }
}
