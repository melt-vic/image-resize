<?php

class Signia_ImageResize_Jpeg extends Signia_ImageResize_Abstract
{
	function getResizedImage()
	{
		if (!file_exists($this->srcImageName)) {
         return $this->error_message;
      }
      
      $this->srcImage = @imagecreatefromjpeg($this->srcImageName);
      if ($this->initCheck() && $this->sizeControl()) {
         $this->destImage = imagecreatetruecolor($this->destWidth, $this->destHeight); // Esto sería mejor moverlo a la clase.
         if ($this->background) {
            $this->resizeImageWithBackground();
         }else{
            $this->resizeImage();
         }
         imagejpeg($this->destImage, $this->destImageName, 50);

         return true;
      }		
	}
}