<?php
/**
 * Default view for showing Pager links.
 *
 * NOTE:  If you need to make a custom view for the pager, create a
 * separate copy of this file, rename the class (and the file too),
 * and work from there.
 *
 * @package Chitin
 * @subpackage Views
 * @version $Id: PagerView.php 1788 2008-07-07 20:24:27Z gabebug $
 * @author Sean McCann <sean@mudbugmedia.com>
 */


class PagerView extends BaseView {

	/**
	 * Display output of the view
	 *
	 */
	function display () {  ?>
		<div class="<?php echo $this->vars['class_wrapper'];?>">
			<div class="now-showing-area">
				<?php
				/**
				 * When no results are found...
				 */
				if ($this->vars['total_items'] == 0){
					echo "No results were found";
				}

				/**
				 * When all of the results fit onto one page...
				 */
				else if ($this->vars['total_items'] <= $this->vars['url']->get('per_page')) {
					if ($this->vars['total_items'] > 2) {
						echo 'Showing all ';
					}
					echo $this->vars['total_items'] . ' result' . (($this->vars['total_items'] == 1) ? '' : 's');
				}

				else {
					/**
					 * Calculate Start and End of the Results window
					 */
					$start_results = ($this->vars['url']->get('page') - 1) * $this->vars['url']->get('per_page') + 1;
					$end_results = min($start_results + $this->vars['url']->get('per_page') - 1, $this->vars['total_items']);
					echo "Showing Results $start_results - $end_results of {$this->vars['total_items']}";
				} ?>
			</div>
			<div class="links-area">
				<?php
				/**
				 * Link to the previous page
				 */
				if ($this->vars['url']->get('page') > 1){
					echo '<a href="' . $this->vars['url']->getUrlForPage($this->vars['url']->get('page') - 1) . '" class="previous" title="' . $this->vars['previous_page_title'] . '">' . $this->vars['previous_page_text'] . '</a>';
				}
				else {
					echo "<span class='previous'>{$this->vars['previous_page_text']}</span>";
				}
				echo $this->vars['separator'];


				/**
				 * Calculate start and end of sliding page window
				 */
				$sliding_start = $this->vars['url']->get('page') - floor($this->vars['max_pages']/2);
				if ($sliding_start < 1) { $sliding_start = 1; }

				$sliding_end = $sliding_start + $this->vars['max_pages'] - 1;
				if ($sliding_end > $this->vars['total_pages']) {
					$sliding_end = $this->vars['total_pages'];
					$sliding_start = max($sliding_end - $this->vars['max_pages'] + 1, 1);
				}
				if ($sliding_start <= ($this->vars['border_pages'] + 2)) {
					$sliding_end = $this->vars['border_pages'] + floor($this->vars['max_pages']/2) + 3 + $this->vars['trailing_pages'];
					$sliding_start = 1;
				}
				if ($sliding_end >= ($this->vars['total_pages'] - $this->vars['border_pages'] - 1)) {
					$sliding_start = $this->vars['total_pages'] - $this->vars['border_pages'] - ceil($this->vars['max_pages']/2) - 1 - $this->vars['trailing_pages'];
					$sliding_end = $this->vars['total_pages'];
				}

				/**
				 * Calculate border page positions
				 */
				if ($this->vars['border_pages'] > 0) {
					$border_left_start = 1;
					$border_left_end = $this->vars['border_pages'];

					$border_right_end = $this->vars['total_pages'];
					$border_right_start = $this->vars['total_pages'] - $this->vars['border_pages'] + 1;
				}
				else {
					$border_left_start = $border_right_start = $sliding_start;
					$border_left_end = $border_right_end = $sliding_end;
				}


				/**
				 * Link to all numbered pages if there's more than 1
				 */
				if ($this->vars['total_pages'] > 1) {
					for ($i = $border_left_start; $i <= $border_right_end; $i++) {
						/**
						 * Place border gap separators and jump ahead
						 */
						if ($i > $border_left_end && $i < $sliding_start){
							echo '<span class="border-separator">' . $this->vars['border_separator'] . '</span>';
							$i = $sliding_start;
						}

						if ($i > $sliding_end && $i < $border_right_start){
							echo '<span class="border-separator">' . $this->vars['border_separator'] . '</span>';
							$i = $border_right_start;
						}

						if ($i == $this->vars['url']->get('page')) {
							echo "<span class='current'>$i</span>";
						}
						else {
							echo '<a href="' . $this->vars['url']->getUrlForPage($i) . '" title="Page ' . $i . '">' . $i . '</a>';
						}
						echo $this->vars['separator'];
					}
				}


				/**
				 * Link to the next page
				 */
				if ($this->vars['url']->get('page') < $this->vars['total_pages']){
					echo '<a href="' . $this->vars['url']->getUrlForPage($this->vars['url']->get('page') + 1) . '" class="next" title="' . $this->vars['next_page_title'] . '">' . $this->vars['next_page_text'] . '</a>';
				}
				else {
					echo "<span class='next'>{$this->vars['next_page_text']}</span>";
				}
				echo $this->vars['separator'];
				?>
			</div>
		</div>
		<?php
	}
}
?>
