<?php
/**
 * Template Name: Search Results
 */

/**
 * @var Apex27 $apex27
 */

$text_domain = "apex27";

$plugin_url = $apex27->get_plugin_url();
$plugin_dir = $apex27->get_plugin_dir();

get_header();

$search_results = $apex27->get_search_results();
if(!$search_results) {
	?>
	<div class="apex27-container">
		<h2 dir="auto"><?=htmlspecialchars(__("Error", $text_domain))?></h2>
		<p dir="auto"><?=htmlspecialchars(__("Cannot retreive properties at this time. Please try again later.", $text_domain))?></p>
	</div>
	<?php
	get_footer();
	return;
}

$query = $_GET;

$page = (int) (get_query_var("page") ?: 1);
$page_size = 10;

$prev_page_query = array_merge($_GET, ["page" => $page - 1]);
$prev_page_url = "/property-search/?" . http_build_query($prev_page_query);

$next_page_query = array_merge($_GET, ["page" => $page + 1]);
$next_page_url = "/property-search/?" . http_build_query($next_page_query);

$has_listings = $search_results->listingCount > 0;

$pagination_position = $apex27->get_pagination_position();
$has_top_pagination = strpos($pagination_position, "t") !== false;
$has_bottom_pagination = strpos($pagination_position, "b") !== false;

if($search_results->listingCount === 0) {
	$page_info = __("No properties", $text_domain);
}
else if($search_results->listingCount <= $page_size) {
	$page_info = sprintf(_n("Showing %d property", "Showing %d properties", $search_results->listingCount, $text_domain), $search_results->listingCount);
}
else {
	$offset = ($page - 1) * $page_size;

	$first_item = $offset + 1;
	$last_item = $offset + $page_size;
	if($last_item > $search_results->listingCount) {
		$last_item = $search_results->listingCount;
	}

	$page_info =  sprintf(__("Showing %d-%d of %d properties", $text_domain), $first_item, $last_item, $search_results->listingCount);
}

$render_pagination = static function() use ($page, $prev_page_url, $text_domain, $search_results, $next_page_url) {
	?>
	<div class="d-flex my-5">
		<div class="flex-fill" dir="auto">
			<?php
			if($page !== 1) {
				?>
				<a href="<?=htmlspecialchars($prev_page_url)?>"><?=htmlspecialchars(__("Previous Page", $text_domain))?></a>
				<?php
			}
			?>

		</div>
		<div class="flex-fill text-center" dir="auto">
			<?=sprintf(
				__("Page %d of %d", $text_domain),
				$page,
				$search_results->pageCount
			)?>
		</div>
		<div class="flex-fill text-right" dir="auto">
			<?php
			if($page < $search_results->pageCount) {
				?>
				<a href="<?=htmlspecialchars($next_page_url)?>"><?=htmlspecialchars(__("Next Page", $text_domain))?></a>
				<?php
			}
			?>
		</div>
	</div>
	<?php
};

?>

<div class="apex27-container">

	<?php
	require $plugin_dir . "/includes/search_form.php";
	get_template_part("search_form");
	?>

	<h2 dir="auto"><?=htmlspecialchars(__("Properties", $text_domain))?></h2>

	<?php

	$properties = $search_results->listings;
	$markers = $search_results->markers;

	if($has_listings) {
		?>
		<div class="d-flex mb-3" style="align-items: center">
			<div class="flex-fill">
				<h6 style="margin: 0;"><?=htmlspecialchars($page_info)?></h6>
			</div>
			<?php
			if($markers && $apex27->has_google_api_key()) {
				?>
				<div>
					<button id="apex27-toggle-map-button" type="button" class="btn">
						<?=htmlspecialchars(__("Toggle Map", $text_domain))?>
					</button>
				</div>
				<?php
			}
			?>
		</div>

		<?php
	}
	else {
		?>
		<h6 class="mb-5" dir="auto"><?=htmlspecialchars(__("No properties to display.", $text_domain))?></h6>
		<?php
	}

	if($markers && $apex27->has_google_api_key()) {
		?>
		<input type="hidden" id="listings-json" value="<?=htmlspecialchars(json_encode($markers))?>" />

		<div id="apex27-map-container" style="display: none;">
			<div class="mb-3" id="apex27-map" style="height: 480px; background: rgba(0, 0, 0, .1);">
			</div>
		</div>
		<?php
	}

	if($has_listings && $has_top_pagination) {
		$render_pagination();
	}

	foreach($properties as $property) {

		$thumbnail_url = $property->thumbnailUrl ?: $plugin_url . "assets/img/property.png";
		$slug = $apex27->get_property_slug($property);
		$property_url = "/property-details/{$property->transactionTypeRoute}/{$slug}/{$property->id}";
		$featured = !empty($property->isFeatured);

		$is_commercial = strpos($property->transactionTypeRoute, "commercial") === 0;

		$featured_class = "";
		if($property->isFeatured) {
			$featured_class = "featured";
		}

		?>
		<div class="card property-card <?=$featured_class?> mb-3" data-featured="<?=json_encode($featured)?>">
			<div class="card-body">
				<div class="d-flex">
					<a class="property-card-image" href="<?=htmlspecialchars($property_url)?>">
						<img class="img-fluid" src="<?=htmlspecialchars($thumbnail_url)?>" alt="" />
						<?php
						if($property->imageOverlayText) {
							?>
							<span class="property-overlay-text">
								<?=htmlspecialchars($property->imageOverlayText)?>
							</span>
							<?php
						}
						?>
					</a>
					<div class="property-card-details">

						<div class="mb-3" dir="auto">

							<?php
							if(!$is_commercial) {
								?>
								<div class="text-brand" style="float: right">
									<span title="<?=htmlspecialchars(__("Bedrooms", $text_domain))?>"><i class="fa fa-fw fa-bed"></i> <?=htmlspecialchars($property->bedrooms)?></span>
									<span title="<?=htmlspecialchars(__("Bathrooms", $text_domain))?>"><i class="fa fa-fw fa-bath"></i> <?=htmlspecialchars($property->bathrooms)?></span>
									<span title="<?=htmlspecialchars(__("Living Rooms", $text_domain))?>"><i class="fa fa-fw fa-couch"></i> <?=htmlspecialchars($property->livingRooms)?></span>
								</div>
								<?php
							}

							if($property->isFeatured) {
								?>
								<span class="featured-badge">
									<?=htmlspecialchars(__("Featured Property", $text_domain))?>
								</span>
								<br />
								<?php
							}

							?>

							<strong>
								<?=htmlspecialchars($property->displayAddress)?>
							</strong>
						</div>

						<div class="mb-3" dir="auto">
							<strong class="text-brand">
								<?=htmlspecialchars($property->displayPrice)?>
								<small><?=htmlspecialchars($property->pricePrefix)?></small>
							</strong><br />
							<?=htmlspecialchars($property->subtitle)?>
						</div>

						<div class="flex-fill">
							<p dir="auto">
								<?=htmlspecialchars($property->summary)?>
							</p>

							<?php

							if($property->incomeDescription) {
								?>
								<p dir="auto">
									<?=htmlspecialchars(__("Gross Income", $text_domain))?>: <?=nl2br(htmlspecialchars($property->incomeDescription))?>
								</p>
								<?php
							}

							if($property->grossYield) {
								?>
								<p dir="auto">
									<?=htmlspecialchars(__("Gross Yield", $text_domain))?>: <?=htmlspecialchars($property->grossYield)?>
								</p>
								<?php
							}

							if($property->saleFee && $property->saleFeePayableByBuyer) {
								?>
								<p dir="auto">
									<?=htmlspecialchars(__("Buyer Fee", $text_domain))?>: <?=htmlspecialchars($property->saleFee)?> + VAT
								</p>
								<?php
							}
							?>
						</div>

						<div class="div" dir="auto">
							<a class="btn btn-brand" href="<?=htmlspecialchars($property_url)?>">
								<?=htmlspecialchars(__("Property Details", $text_domain))?>
							</a>

							<a class="btn btn-brand" href="<?=htmlspecialchars($property_url)?>#property-details-contact-form">
								<?=htmlspecialchars(__("Make Enquiry", $text_domain))?>
							</a>
						</div>

					</div>
				</div>
			</div>
		</div>
		<?php
	}

	if($has_listings && $has_bottom_pagination) {
		$render_pagination();
	}

	$apex27->include_template(
		$apex27->get_template_path("enquiry-form")
	);

	if($search_results->showLogo) {
		require $apex27->get_footer_path();
	}
	?>
</div>

<?php get_footer(); ?>
