<?php

class Beatmaps {
	const PageID = 32;
	const URL = 'Beatmaps';
	const Title = 'Vipsu - Beatmaplist';

	public function P() {

		
		$c = 0;
		P::GlobalAlert();
		APITokens::PrintScript('var PageID = 1;');
		echo '
		<div id="content">
				<div align="center">
				<h1><i class="fa fa-music"></i> Beatmaps</h1>

				<input type="text" style="width:400px; margin-top: 10px;" class="form-control beatmap_search" onkeypress="searchBM()" id="serachquery" placeholder="Search beatmaps...">
				<br>

			<div class="beatmapsets-search-filter"><a href="#" class="beatmapsets-search-filter__item beatmapsets-search-filter__item--active" value="-1">Any</a><a href="#" class="beatmapsets-search-filter__item undefined" value="0" data-filter-value="0">osu!</a><a href="#" class="beatmapsets-search-filter__item undefined" value="1" data-filter-value="1">osu!taiko</a><a href="#" class="beatmapsets-search-filter__item undefined" value="2" data-filter-value="2">osu!catch</a><a href="#" class="beatmapsets-search-filter__item undefined" value="3" data-filter-value="3">osu!mania</a></div>

			<br><input type="checkbox" class="checkbox" id="checkbox"><label for="checkbox">Ranked/Loved only on Vipsu</label>
				
				<div class="alert alert-up" role="alert"><div class="container" id="beatmaps" style="width: 100%;">
				';


			echo '</div></div><br><hr>
			</div>

		</div>';
	}
}
