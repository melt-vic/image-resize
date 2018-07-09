<?php
class Signia_ImageResize_Gif extends Signia_ImageResize_Abstract
{
   function getResizedImage()
   {
      if (!file_exists($this->srcImageName)) {
         return $this->error_message;
      }
      $this->srcImage = imagecreatefromgif($this->srcImageName);
      if ($this->initCheck() && $this->sizeControl()) {
         $transparency = imagecolortransparent($this->srcImage);
         if ($transparency != -1) {
            $this->destImage    = imagecreatetruecolor($this->aResult[0], $this->aResult[1]);
            $colorTransparent   = imagecolorsforindex($this->srcImage, $transparency);
            $idColorTransparent = imagecolorallocatealpha($this->destImage, $colorTransparent['red'], $colorTransparent['green'], $colorTransparent['blue'], $colorTransparent['alpha']);
            imagefill($this->destImage, 0, 0, $idColorTransparent);
            imagecolortransparent($this->destImage, $idColorTransparent);
         } else {
            $this->destImage = imagecreatetruecolor($this->aResult[0], $this->aResult[1]);
         }
         if ($this->background) {
            $this->resizeImageWithBackground();
         }else{
            $this->resizeImage(true);
         }
         imagegif($this->destImage, $this->destImageName);
         return true;
      }
   }
}