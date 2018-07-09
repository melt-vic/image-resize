<?php

class Signia_ImageResize_Factory
{
	static public function getInstanceOf($srcImageName, $destImageName, $newSize)
	{
		$aux       = explode(".", $destImageName);
		$extension = end($aux);
		if (preg_match("/jpg|JPG|jpeg|JPEG/", $extension)) {
			$extension = "jpeg";
		}
		$imageResizer = "Signia_ImageResize_" . ucfirst($extension);

		return new $imageResizer($srcImageName, $destImageName, $newSize);
	}
}