<?php
defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Site\View\Categories\Html  $this */
?>

<div class="item-page{{{ $this->params->get('pageclass_sfx') }}}">
	@if($this->params->get('show_page_heading'))
	<div class="page-header">
		<h1>{{{ $this->params->get('page_heading', $menu->title) }}}</h1>
	</div>
	@endif

	@include('site:com_ars/Categories/generic', ['section' => 'normal', 'items' => $this->items['normal'], 'title' => 'ARS_CATEGORY_NORMAL'])
</div>