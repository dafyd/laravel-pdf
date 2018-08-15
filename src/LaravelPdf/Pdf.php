<?php

namespace niklasravnsborg\LaravelPdf;

use Config;
use Mpdf;

/**
 * Laravel PDF: mPDF wrapper for Laravel 5
 *
 * @package laravel-pdf
 * @author Niklas Ravnsborg-Gjertsen
 */
class Pdf {

	protected $config = [];
	protected $html = '';

	public function __construct($html = '', $config = [])
	{
		$this->config = $config;
		$this->html = $html;

		$mpdf_config = [
			'mode'                 =>   $this->getConfig('mode'),              // mode - default ''
			'format'               =>   $this->getConfig('format'),            // format - A4, for example, default ''
			'margin_left'          =>   $this->getConfig('margin_left'),       // margin_left
			'margin_right'         =>   $this->getConfig('margin_right'),      // margin right
			'margin_top'           =>   $this->getConfig('margin_top'),        // margin top
			'margin_bottom'        =>   $this->getConfig('margin_bottom'),     // margin bottom
			'margin_header'        =>   $this->getConfig('margin_header'),     // margin header
			'margin_footer'        =>   $this->getConfig('margin_footer'),     // margin footer
			'tempDir'              =>   $this->getConfig('tempDir')            // margin footer
		];

		// Handle custom fonts
		$mpdf_config = $this->addCustomFontsConfig($mpdf_config);

		$this->mpdf = new Mpdf\Mpdf($mpdf_config);

		// If you want to change your document title,
		// please use the <title> tag.
		$this->mpdf->SetTitle('Document');

		$this->mpdf->SetAuthor        ( $this->getConfig('author') );
		$this->mpdf->SetCreator       ( $this->getConfig('creator') );
		$this->mpdf->SetSubject       ( $this->getConfig('subject') );
		$this->mpdf->SetKeywords      ( $this->getConfig('keywords') );
		$this->mpdf->SetDisplayMode   ( $this->getConfig('display_mode') );
	}

	protected function getConfig($key)
	{
		if (isset($this->config[$key])) {
			return $this->config[$key];
		} else {
			return Config::get('pdf.' . $key);
		}
	}

	protected function addCustomFontsConfig($mpdf_config)
	{
		if (!Config::has('pdf.font_path') || !Config::has('pdf.font_data')) {
			return $mpdf_config;
		}

		// Get default font configuration
		$fontDirs = (new Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'];
		$fontData = (new Mpdf\Config\FontVariables())->getDefaults()['fontdata'];

		// Merge default with custom configuration
		$mpdf_config['fontDir'] = array_merge($fontDirs, [Config::get('pdf.font_path')]);
		$mpdf_config['fontdata'] = array_merge($fontData, Config::get('pdf.font_data'));

		return $mpdf_config;
	}

	/**
	 * Adds a watermark to the PDF
	 *
	 * @param string $watermark Text or image source
	 * @param string $mode Mode: 'text' or 'image'
	 * @param string $alpha Watermark alpha (0 to 1)
	 * @return static
	 *
	 */
	public function setWatermark($watermark, $mode = 'text', $alpha = '0.2')
	{
		$this->mpdf->{camel_case('show_watermark_'.$mode)} = true;
		$this->mpdf->{camel_case('watermark_'.$mode.'_alpha')} = $alpha;

		return $this->mpdf->{studly_case('set_watermark_'.$mode)}($watermark);
	}

	/**
	 * Encrypts and sets the PDF document permissions
	 *
	 * @param array $permisson Permissons e.g.: ['copy', 'print']
	 * @param string $userPassword User password
	 * @param string $ownerPassword Owner password
	 * @return static
	 *
	 */
	public function setProtection($permisson, $userPassword = '', $ownerPassword = '')
	{
		if (func_get_args()[2] === NULL) {
			$ownerPassword = bin2hex(openssl_random_pseudo_bytes(8));
		};
		return $this->mpdf->SetProtection($permisson, $userPassword, $ownerPassword);
	}

	/**
	 * Output the PDF as a string.
	 *
	 * @return string The rendered PDF as string
	 */
	public function output()
	{
		$this->mpdf->WriteHTML($this->html);
		return $this->mpdf->Output('', 'S');
	}

	/**
	 * Save the PDF to a file
	 *
	 * @param $filename
	 * @return static
	 */
	public function save($filename)
	{
		$this->mpdf->WriteHTML($this->html);
		return $this->mpdf->Output($filename, 'F');
	}

	/**
	 * Make the PDF downloadable by the user
	 *
	 * @param string $filename
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function download($filename = 'document.pdf')
	{
		$this->mpdf->WriteHTML($this->html);
		return $this->mpdf->Output($filename, 'D');
	}

	/**
	 * Return a response with the PDF to show in the browser
	 *
	 * @param string $filename
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function stream($filename = 'document.pdf')
	{
		$this->mpdf->WriteHTML($this->html);
		return $this->mpdf->Output($filename, 'I');
	}
}
