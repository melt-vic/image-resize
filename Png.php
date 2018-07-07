<?php

class Signia_ImageResize_Png extends Signia_ImageResize_Abstract
{

   function getResizedImage()
   {
      if (!file_exists($this->srcImageName)) {
         return $this->error_message;
      }
      $this->srcImage = imagecreatefrompng($this->srcImageName);
      if ($this->initCheck() && $this->sizeControl()) {
         $this->destImage = imagecreatetruecolor($this->aResult[0], $this->aResult[1]);
         imagealphablending($this->destImage, false);
         imagesavealpha($this->destImage, true);
         if ($this->background) {
            $this->resizeImageWithBackground();
         } else {
            $this->resizeImage(true);
         }
         imagepng($this->destImage, $this->destImageName, 9);

         return true;
      }
   }
}
