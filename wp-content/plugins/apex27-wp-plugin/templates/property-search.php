<?php
/**
 * Template Name: Search Results (Enhanced Grid)
 * Aktonz‑inspired property search layout adapted to Aktonz brand.
 *
 * @var Apex27 $apex27
 */

$text_domain = 'apex27';
$plugin_url   = $apex27->get_plugin_url();
$plugin_dir   = $apex27->get_plugin_dir();

get_header();

$search_results = $apex27->get_search_results();
if(!$search_results) {
	echo '<div class="apex27-container"><h2>'.htmlspecialchars(__('Error',$text_domain)).'</h2><p>'.htmlspecialchars(__('Cannot retreive properties at this time. Please try again later.',$text_domain)).'</p></div>';
	get_footer();
	return;
}

$page      = (int) (get_query_var('page') ?: 1);
$page_size = 24; // increase grid density

$prev_page_url = '/property-search/?'.http_build_query(array_merge($_GET,[ 'page'=>$page-1 ]));
$next_page_url = '/property-search/?'.http_build_query(array_merge($_GET,[ 'page'=>$page+1 ]));

$has_listings = $search_results->listingCount > 0;

// Compute page info text
if($search_results->listingCount === 0) {
	$page_info = __('No properties',$text_domain);
} elseif($search_results->listingCount <= $page_size) {
	$page_info = sprintf(_n('Showing %d property','Showing %d properties',$search_results->listingCount,$text_domain), $search_results->listingCount);
} else {
	$offset = ($page-1)*$page_size;
	$first  = $offset+1;
	$last   = min($offset+$page_size,$search_results->listingCount);
	$page_info = sprintf(__('Showing %d–%d of %d properties',$text_domain),$first,$last,$search_results->listingCount);
}

$render_pagination = static function() use ($page,$prev_page_url,$next_page_url,$text_domain,$search_results){ ?>
	<div class="apex27-pagination d-flex align-items-center my-4">
		<div class="flex-fill">
			<?php if($page>1){ ?>
				<a class="apex27-page-link" href="<?=htmlspecialchars($prev_page_url)?>">&laquo; <?=htmlspecialchars(__('Previous',$text_domain))?></a>
			<?php } ?>
		</div>
		<div class="flex-fill text-center small text-muted">
			<?=sprintf(__('Page %d of %d',$text_domain), $page, $search_results->pageCount)?>
		</div>
		<div class="flex-fill text-right">
			<?php if($page < $search_results->pageCount){ ?>
				<a class="apex27-page-link" href="<?=htmlspecialchars($next_page_url)?>"><?=htmlspecialchars(__('Next',$text_domain))?> &raquo;</a>
			<?php } ?>
		</div>
	</div>
<?php };

// Inline minimal CSS additions (scoped) – keep colours aligned with existing brand (assuming .text-brand / .btn-brand styles already use primary brand colour)
?>
<style>
/* --- Enhanced Grid Styles --- */
.apex27-results-header {display:flex;flex-wrap:wrap;gap:1rem;align-items:center;margin:1rem 0 1.25rem;}
.apex27-results-header h1{font-size:1.35rem;margin:0;font-weight:600;}
.apex27-filters-bar{display:flex;flex-wrap:wrap;gap:.5rem;}
.apex27-filters-bar .filter-chip{background:#f4f7f8;color:#222;border:1px solid #d7e1e4;border-radius:20px;padding:.35rem .85rem;font-size:.75rem;display:inline-flex;align-items:center;gap:.35rem;}
.apex27-filters-bar .filter-chip button{background:none;border:0;font-size:14px;line-height:1;cursor:pointer;color:#555;padding:0;}
.apex27-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:18px;}
.apex27-grid-card{position:relative;border:1px solid #e2e8ea;border-radius:6px;overflow:hidden;background:#fff;display:flex;flex-direction:column;transition:box-shadow .18s,transform .18s;}
.apex27-grid-card:hover{box-shadow:0 4px 18px rgba(0,0,0,.08);transform:translateY(-2px);}
.apex27-grid-card img{width:100%;aspect-ratio:4/3;object-fit:cover;background:#f2f2f2;}
.apex27-label-strip{position:absolute;top:8px;left:8px;display:flex;flex-direction:column;gap:4px;}
.apex27-badge{background:#0c5c56;color:#fff;font-size:.6rem;letter-spacing:.5px;text-transform:uppercase;padding:4px 7px;border-radius:3px;font-weight:600;box-shadow:0 1px 2px rgba(0,0,0,.25);}
.apex27-badge.featured{background:#d39e00;}
.apex27-card-body{padding:10px 12px;display:flex;flex-direction:column;flex:1;}
.apex27-price{font-weight:700;color:var(--aktonz-brand,#0c5c56);margin:0 0 4px;font-size:.95rem;}
.apex27-address{font-size:.78rem;font-weight:600;line-height:1.2;margin:0 0 4px;min-height:32px;}
.apex27-subtitle{font-size:.68rem;color:#555;min-height:28px;margin:0 0 6px;}
.apex27-meta{display:flex;gap:10px;font-size:.6rem;text-transform:uppercase;color:#0c5c56;font-weight:600;margin:0 0 6px;}
.apex27-summary{font-size:.65rem;line-height:1.25;color:#444;margin:0 0 8px;flex:1;}
.apex27-view-link{font-size:.65rem;font-weight:600;letter-spacing:.5px;text-transform:uppercase;text-decoration:none;display:inline-block;padding:6px 10px;border:1px solid #0c5c56;color:#0c5c56;border-radius:4px;transition:background .2s,color .2s;}
.apex27-view-link:hover{background:#0c5c56;color:#fff;}
.apex27-map-toggle{margin-left:auto;}
.apex27-valuation-cta{background:#0c5c56;color:#fff;border-radius:8px;padding:50px 30px;margin:70px 0 40px;text-align:center;position:relative;overflow:hidden;}
.apex27-valuation-cta h2{color:#fff;font-weight:600;font-size:1.6rem;margin:0 0 10px;}
.apex27-valuation-cta p{margin:0 0 22px;font-size:.9rem;}
.apex27-valuation-cta .btn-brand.alt{background:#fff;color:#0c5c56;font-weight:600;}
@media (max-width:640px){
  .apex27-grid{grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;}
  .apex27-results-header{flex-direction:column;align-items:flex-start;}
  .apex27-map-toggle{margin-left:0;}
}
</style>

<div class="apex27-container">
	<?php // existing search form include
	require $plugin_dir.'/includes/search_form.php';
	get_template_part('search_form');
	?>

	<div class="apex27-results-header">
		<h1><?=htmlspecialchars(__('Property results',$text_domain))?></h1>
		<div class="small text-muted"><?=htmlspecialchars($page_info)?></div>
		<div class="apex27-map-toggle">
			<?php if($search_results->markers && $apex27->has_google_api_key()) { ?>
				<button id="apex27-toggle-map-button" type="button" class="btn btn-sm btn-brand"><?=htmlspecialchars(__('Map View',$text_domain))?></button>
			<?php } ?>
		</div>
	</div>

	<?php
	// dynamic filter chips: derive from GET params (excluding paging)
	$chips = [];
	foreach($_GET as $k=>$v){ if($k==='page'||$v==='') continue; $label = ucfirst(str_replace('_',' ',$k)).': '.$v; $chips[]=[ 'key'=>$k,'label'=>$label ]; }
	if($chips){ echo '<div class="apex27-filters-bar mb-3">'; foreach($chips as $chip){ $remove = $_GET; unset($remove[$chip['key']]); $remove_url = '/property-search/?'.http_build_query($remove); echo '<span class="filter-chip">'.htmlspecialchars($chip['label']).' <a href="'.htmlspecialchars($remove_url).'" aria-label="'.htmlspecialchars(__('Remove filter',$text_domain)).'">&times;</a></span>'; } echo '</div>'; }
	?>

	<?php if(!$has_listings){ ?>
		<p class="mb-5"><?=htmlspecialchars(__('No properties to display. Try adjusting your filters.',$text_domain))?></p>
	<?php } ?>

	<?php if($has_listings) { $render_pagination(); } ?>

	<?php if($search_results->markers && $apex27->has_google_api_key()) { ?>
		<input type="hidden" id="listings-json" value='<?=htmlspecialchars(json_encode($search_results->markers))?>' />
		<div id="apex27-map-container" style="display:none;">
			<div id="apex27-map" style="height:480px;background:rgba(0,0,0,.08);" class="mb-4"></div>
		</div>
	<?php } ?>

	<?php if($has_listings) { ?>
	<div class="apex27-grid" id="apex27-grid-results">
		<?php
		$properties = $search_results->listings;
		foreach($properties as $property) {
			$thumbnail_url = $property->thumbnailUrl ?: $plugin_url.'assets/img/property.png';
			$slug = $apex27->get_property_slug($property);
			$property_url = "/property-details/{$property->transactionTypeRoute}/{$slug}/{$property->id}";
			$featured = !empty($property->isFeatured);
			$is_commercial = strpos($property->transactionTypeRoute,'commercial')===0;
			$badges = [];
			if($featured) $badges[] = ['Featured','featured'];
			if(!empty($property->imageOverlayText)) $badges[] = [$property->imageOverlayText,'overlay'];
			?>
			<div class="apex27-grid-card" data-featured="<?=htmlspecialchars(json_encode($featured))?>">
				<a href="<?=htmlspecialchars($property_url)?>" class="apex27-thumb-link" aria-label="<?=htmlspecialchars(__('View property',$text_domain))?>">
					<img src="<?=htmlspecialchars($thumbnail_url)?>" alt="" loading="lazy" />
					<?php if($badges){ echo '<div class="apex27-label-strip">'; foreach($badges as $b){ echo '<span class="apex27-badge '.htmlspecialchars($b[1]).'">'.htmlspecialchars($b[0]).'</span>'; } echo '</div>'; } ?>
				</a>
				<div class="apex27-card-body">
					<p class="apex27-price"><?=htmlspecialchars($property->displayPrice)?> <small><?=htmlspecialchars($property->pricePrefix)?></small></p>
					<p class="apex27-address"><?=htmlspecialchars($property->displayAddress)?></p>
					<p class="apex27-subtitle"><?=htmlspecialchars($property->subtitle)?></p>
					<?php if(!$is_commercial){ ?>
						<div class="apex27-meta">
							<span title="<?=htmlspecialchars(__('Bedrooms',$text_domain))?>"><i class="fa fa-bed"></i> <?=htmlspecialchars($property->bedrooms)?></span>
							<span title="<?=htmlspecialchars(__('Bathrooms',$text_domain))?>"><i class="fa fa-bath"></i> <?=htmlspecialchars($property->bathrooms)?></span>
							<span title="<?=htmlspecialchars(__('Living Rooms',$text_domain))?>"><i class="fa fa-couch"></i> <?=htmlspecialchars($property->livingRooms)?></span>
						</div>
					<?php } ?>
					<p class="apex27-summary"><?=htmlspecialchars(mb_strimwidth($property->summary,0,180,'…'))?></p>
					<div>
						<a class="apex27-view-link" href="<?=htmlspecialchars($property_url)?>"><?=htmlspecialchars(__('Details',$text_domain))?></a>
						<a class="apex27-view-link" href="<?=htmlspecialchars($property_url)?>#property-details-contact-form"><?=htmlspecialchars(__('Enquiry',$text_domain))?></a>
					</div>
				</div>
			</div>
		<?php } ?>
	</div>
	<?php } ?>

	<?php if($has_listings) { $render_pagination(); } ?>

	<div class="apex27-valuation-cta">
		<h2><?=htmlspecialchars(__('We offer a free home valuation service',$text_domain))?></h2>
		<p><?=htmlspecialchars(__('Curious what your property is worth? Get an expert valuation from our local team.',$text_domain))?></p>
		<a href="/valuation" class="btn btn-brand alt"><?=htmlspecialchars(__('Book Your Free Valuation',$text_domain))?></a>
	</div>

	<?php
	// Reuse enquiry form beneath results
	$apex27->include_template($apex27->get_template_path('enquiry-form'));
	if($search_results->showLogo) { require $apex27->get_footer_path(); }
	?>
</div>

<?php get_footer(); ?>
