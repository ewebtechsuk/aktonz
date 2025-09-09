<?php
/**
 * Template Name: Property Details
 * @var Apex27 $apex27
 */

$text_domain = "apex27";
$options = $apex27->get_portal_options();
$details = $apex27->get_property_details();
$apex27->set_listing_details($details);
$featured = !empty($details->isFeatured);
get_header();
if(!$details) {
    ?>
    <div class="apex27-container">
        <h2 dir="auto"><?=htmlspecialchars(__("Error", $text_domain))?></h2>
        <p dir="auto"><?=htmlspecialchars(__("Cannot retrieve property details at this time. Please try again later.", $text_domain))?></p>
    </div>
    <?php
    get_footer();
    return;
}
$get_lines = static function($string) {
    $lines = explode("\n", $string);
    $lines = array_map("trim", $lines);
    return array_filter($lines);
};
$description_lines = $get_lines($details->description);
$description_line_1 = array_shift($description_lines);
$property_images = $details->images ?? [];
?>
<style>
.property-header {
    background: #fff;
    border-radius: 1rem;
    box-shadow: 0 2px 16px rgba(0,0,0,0.07);
    margin-bottom: 2rem;
    padding: 2rem;
}
.property-image-slider {
    position: relative;
}
.property-slider-image {
    display: none;
    width: 100%;
    height: 400px;
    object-fit: cover;
    border-radius: 1rem;

    background: #f8f9fa;
    margin-bottom: 1rem;
    transition: opacity 0.2s;
}
.property-slider-image.active {
    display: block;
}
.property-image-slider .slider-control {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0,0,0,0.5);
    color: #fff;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;

}
.property-image-slider .slider-prev { left: 10px; }
.property-image-slider .slider-next { right: 10px; }
@media (max-width: 991px) {
    .property-slider-image { height: 250px; }
}
@media (max-width: 575px) {
    .property-slider-image { height: 180px; }
    .property-media-tabs .property-image-slider,
    .property-media-tabs .property-slider-image {

        width: 100vw;
        margin-left: calc(50% - 50vw);
    }
}
.sticky-sidebar {
    position: sticky;
    top: 2rem;
    z-index: 2;
}
.property-actions .btn {
    margin-bottom: 0.5rem;
    margin-right: 10px;
}
.property-actions .btn:last-child {
    margin-right: 0;
}
.property-meta span {
    font-size: 1.1rem;
    margin-right: 1.5rem;
}
.property-features-list {
    list-style: none;
    padding: 0;
}
.property-features-list li {
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
}
.property-features-list .bullet-text {
    margin-left: 0.5rem;
}
.property-description {
    background: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
}
.property-key-features,
.property-note,
.property-further-details,
#property-details-epcs {
    margin-bottom: 2rem;
}
.property-note textarea { resize: vertical; }
.property-further-details th { width: 40%; }
.property-media-tabs .media-tabs-nav {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}
.property-media-tabs .media-tab-btn {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    cursor: pointer;
    font-weight: 600;
}
.property-media-tabs .media-tab-btn.active {
    background: #007bff;
    color: #fff;
    border-color: #007bff;
}
.media-tab-content { display: none; }
.media-tab-content.active { display: block; }

.property-main {
    display: flex;
    gap: 30px;
}
.property-media-container {
    flex: 0 0 600px;
    max-width: 600px;
}
.property-sidebar {
    flex: 0 0 350px;
    max-width: 350px;
}
@media (max-width: 991px) {
    .property-main {
        flex-direction: column;
    }
    .property-media-container,
    .property-sidebar {
        flex: 1 1 100%;
        max-width: 100%;
    }
    .sticky-sidebar { position: static; top: auto; }
}
</style>
<div class="container-fluid px-0">
    <div class="container">

        <div class="property-header mt-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div>
                    <h1 class="property-title mb-1"><?=htmlspecialchars($details->displayAddress)?></h1>
                    <?php if (!empty($details->propertyType)) : ?>
                        <div class="text-muted mb-2" style="font-size:1.1rem;">
                            <?=htmlspecialchars($details->propertyType)?></div>
                    <?php endif; ?>
                    <?php if($featured): ?>
                        <span class="badge bg-success">Featured</span>
                    <?php endif; ?>
                </div>
                <div class="property-meta d-flex flex-wrap gap-3 mt-2">
                    <span><i class="fa fa-bed"></i> <?=htmlspecialchars($details->bedrooms)?> Beds</span>
                    <span><i class="fa fa-bath"></i> <?=htmlspecialchars($details->bathrooms)?> Baths</span>
                    <span><i class="fa fa-couch"></i> <?=htmlspecialchars($details->livingRooms)?> Living</span>
                </div>
            </div>
            <div class="property-price display-4 text-brand mt-3">
                <?=htmlspecialchars($details->displayPrice)?>
                <small><?=htmlspecialchars($details->pricePrefix)?></small>
            </div>
            <div class="property-actions mt-4 d-flex flex-wrap gap-2" style="gap:10px;">
                <button class="btn btn-lg btn-warning mb-2" onclick="showViewingForm(); return false;">
                    <i class="fa fa-calendar-check"></i> Book Viewing
                </button>

                <button
                    class="btn btn-lg btn-primary mb-2"
                    type="button"
                    onclick="if (typeof showOfferForm === 'function') { showOfferForm(); }">
                    <i class="fa fa-hand-holding-usd"></i> Make Offer
                </button>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-12">

                <div class="property-media-tabs mb-4">
                    <div class="media-tabs-nav">
                        <button class="media-tab-btn active" data-target="photos">Photos</button>
                        <?php if($details->floorplans) { ?>
                        <button class="media-tab-btn" data-target="floorplan">Floorplan</button>
                        <?php } ?>
                        <button class="media-tab-btn" data-target="map">Location</button>
                    </div>
                    <?php if($property_images) { ?>
                    <div id="tab-photos" class="media-tab-content active">
                        <div id="propertyImageSlider" class="property-image-slider">
                            <?php foreach($property_images as $idx => $img) { ?>
                            <img class="property-slider-image<?=$idx === 0 ? ' active' : ''?>" src="<?=htmlspecialchars($img->url)?>" alt="<?=htmlspecialchars($img->caption ?? $details->displayAddress)?>" />
                            <?php } ?>
                            <?php if(count($property_images) > 1) { ?>
                            <button class="slider-control slider-prev" type="button" aria-label="<?=htmlspecialchars(__('Previous', $text_domain))?>">&#10094;</button>
                            <button class="slider-control slider-next" type="button" aria-label="<?=htmlspecialchars(__('Next', $text_domain))?>">&#10095;</button>

                            <?php } ?>
                        </div>
                    </div>
                    <?php } ?>
                    <?php if($details->floorplans) { ?>
                    <div id="tab-floorplan" class="media-tab-content">
                        <?php foreach($details->floorplans as $plan) { ?>
                            <img class="w-100 mb-3" src="<?=htmlspecialchars($plan->url)?>" alt="<?=htmlspecialchars($plan->caption ?? 'Floorplan')?>" />
                        <?php } ?>
                    </div>
                    <?php } ?>
                    <div id="tab-map" class="media-tab-content">
                        <iframe width="100%" height="400" style="border:0;" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://www.google.com/maps?q=<?=urlencode($details->displayAddress)?>&output=embed"></iframe>
                    </div>
                </div>
                <?php if($details->bullets) { ?>
                <section class="property-key-features">
                    <h3 class="text-brand mb-3" dir="auto"><i class="fa fa-star text-warning"></i> <?=htmlspecialchars(__('Key Features', $text_domain))?></h3>
                    <ul class="property-features-list">
                        <?php foreach($details->bullets as $bullet) { ?>
                        <li dir="auto">
                            <i class="fa fa-check-circle text-success"></i>
                            <span class="bullet-text"> <?=htmlspecialchars($bullet)?></span>
                        </li>
                        <?php } ?>
                    </ul>
                </section>
                <?php } ?>
                <section class="property-note">
                    <h3 class="text-brand mb-3" dir="auto"><?=htmlspecialchars(__('Add a note', $text_domain))?></h3>
                    <textarea id="property-note" class="form-control" rows="3"></textarea>
                    <button id="save-note-btn" class="btn btn-outline-secondary mt-2"><?=htmlspecialchars(__('Save', $text_domain))?></button>
                    <div id="note-saved" class="text-success small mt-1" style="display:none;"><?=htmlspecialchars(__('Saved', $text_domain))?></div>
                </section>
                <?php if(!empty($details->details)) { ?>
                <section class="property-further-details">
                    <h3 class="text-brand mb-3" dir="auto"><?=htmlspecialchars(__('Further details', $text_domain))?></h3>
                    <table class="table table-striped property-details-table">
                        <?php foreach($details->details as $k => $v) { ?>
                        <tr>
                            <th><?=htmlspecialchars($k)?></th>
                            <td><?=htmlspecialchars(is_scalar($v) ? $v : '')?></td>
                        </tr>
                        <?php } ?>
                    </table>
                </section>
                <?php } ?>
                <?php if($details->epcs) { ?>
                <section id="property-details-epcs">
                    <h3 class="text-brand mb-3" dir="auto"><?=htmlspecialchars(__('Energy Performance Certificates', $text_domain))?></h3>
                    <?php foreach($details->epcs as $epc) { ?>
                    <img class="img-fluid mb-3" src="<?=htmlspecialchars($epc->url)?>" alt="<?=htmlspecialchars($epc->caption ?? 'EPC')?>" />
                    <?php } ?>
                </section>
                <?php } ?>
                <div class="property-description">
                    <h4 class="text-brand mb-3" dir="auto"><?=htmlspecialchars(__('Description', $text_domain))?></h4>
                    <?php if($details->description) { ?>
                        <p dir="auto" class="mb-2"><?=htmlspecialchars($description_line_1)?></p>
                        <?php foreach($description_lines as $line) { ?>
                            <p dir="auto" class="mb-2"><?=$apex27->format_text($line)?></p>
                        <?php } ?>
                    <?php } else { ?>
                        <p dir="auto"><em><?=htmlspecialchars(__('No description is available for this property.', $text_domain))?></em></p>
                    <?php } ?>
                </div>
                <?php if($details->brochures) {
                    $brochure_count = count($details->brochures);
                    if($brochure_count > 1) { ?>
                    <div class="mb-4" id="property-details-brochures">
                        <h3 class="text-brand mb-3" dir="auto"><?=htmlspecialchars(__('Brochures', $text_domain))?></h3>
                        <ul class="list-unstyled">
                            <?php foreach($details->brochures as $brochure) { ?>
                            <li><a href="<?=htmlspecialchars($brochure->url)?>" target="_blank"><i class="fa fa-file-pdf"></i> <?=htmlspecialchars($brochure->name ?? __('Brochure', $text_domain))?></a></li>
                            <?php } ?>
                        </ul>
                    </div>
                    <?php } else {
                        $brochure = $details->brochures[0]; ?>
                    <div class="mb-4" id="property-details-brochures">
                        <a href="<?=htmlspecialchars($brochure->url)?>" class="btn btn-outline-brand" target="_blank">
                            <i class="fa fa-file-pdf"></i> <?=htmlspecialchars(__('Brochure', $text_domain))?>
                        </a>

                    </div>
                    <?php }
                } ?>
            </div>

        </div>
    </div>
</div>
<!-- Modal for Book Viewing -->
<div id="viewing-form-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeViewingForm();">&times;</span>
        <form id="bookViewingForm" method="post" action="<?=esc_url( admin_url('admin-post.php') )?>">
            <input type="hidden" name="action" value="apex27_book_viewing">
            <input type="hidden" name="property_id" value="<?=htmlspecialchars($details->id)?>">
            <h4>Book a Viewing</h4>
            <div class="mb-2">
                <label>Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-2">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-2">
                <label>Phone</label>
                <input type="text" name="phone" class="form-control" required>
            </div>
            <div id="viewing-slots">
                <label>Viewing Slot 1</label>
                <div class="d-flex gap-2 mb-2">
                    <input type="date" name="slots[0][date]" class="form-control" required>
                    <input type="time" name="slots[0][time]" class="form-control" required>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary mb-2" id="addSlotBtn">Add Another Slot</button>
            <button type="submit" class="btn btn-success w-100">Book Viewing</button>
        </form>
    </div>
</div>
<script>
function showViewingForm() {
    document.getElementById('viewing-form-modal').style.display = 'block';
}
function closeViewingForm() {
    document.getElementById('viewing-form-modal').style.display = 'none';
}
(function() {
    var slotCount = 1;
    document.getElementById('addSlotBtn').addEventListener('click', function() {
        if(slotCount >= 3) return;
        var container = document.getElementById('viewing-slots');
        var idx = slotCount;
        var slotDiv = document.createElement('div');
        slotDiv.className = 'd-flex gap-2 mb-2';
        slotDiv.innerHTML = '<label style="width:100%">Viewing Slot ' + (idx+1) + '</label>' +
            '<input type="date" name="slots['+idx+'][date]" class="form-control" required>' +
            '<input type="time" name="slots['+idx+'][time]" class="form-control" required>';
        container.appendChild(slotDiv);
        slotCount++;
        if(slotCount >= 3) this.disabled = true;
    });
})();
(function() {
    var buttons = document.querySelectorAll('.media-tab-btn');
    var contents = {
        photos: document.getElementById('tab-photos'),
        floorplan: document.getElementById('tab-floorplan'),
        map: document.getElementById('tab-map')
    };
    buttons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var target = this.getAttribute('data-target');
            buttons.forEach(function(b) { b.classList.remove('active'); });
            for (var key in contents) {
                if(contents[key]) contents[key].classList.remove('active');
            }
            this.classList.add('active');
            if(contents[target]) contents[target].classList.add('active');
        });
    });
})();
(function(){
    var noteKey = 'apex27-note-' + <?=json_encode($details->id ?? '')?>;
    var textarea = document.getElementById('property-note');
    var saved = document.getElementById('note-saved');
    var btn = document.getElementById('save-note-btn');
    if(textarea && btn){
        textarea.value = localStorage.getItem(noteKey) || '';
        btn.addEventListener('click', function(e){
            e.preventDefault();
            localStorage.setItem(noteKey, textarea.value);
            saved.style.display='block';
            setTimeout(function(){ saved.style.display='none'; }, 2000);
        });
    }
})();
(function(){
    var slider = document.getElementById('propertyImageSlider');
    if(!slider) return;
    var images = slider.querySelectorAll('.property-slider-image');
    var prev = slider.querySelector('.slider-prev');
    var next = slider.querySelector('.slider-next');
    var index = 0;
    function show(i){
        images[index].classList.remove('active');
        index = (i + images.length) % images.length;
        images[index].classList.add('active');
    }
    if(prev) prev.addEventListener('click', function(){ show(index-1); });
    if(next) next.addEventListener('click', function(){ show(index+1); });

})();
</script>
<?php
// --- Additional Aktonz-style sections (CTA, related properties, local intelligence) ---
?>
<style>
/* Aktonz-inspired extended layout */
.apex27-section { padding: 3rem 0; }
.apex27-section.alt { background:#00665a; color:#fff; }
.apex27-section.alt h2,.apex27-section.alt h3 { color:#fff; }
.apex27-cta-box { text-align:center; padding:3rem 1rem; background:#00665a; color:#fff; border-radius:0.75rem; }
.apex27-cta-box .btn { margin-top:1rem; }
.apex27-related { display:flex; gap:1rem; overflow-x:auto; scroll-snap-type:x mandatory; }
.apex27-related-card { flex:0 0 260px; background:#fff; border:1px solid #e3e6e9; border-radius:0.75rem; box-shadow:0 2px 8px rgba(0,0,0,.05); scroll-snap-align:start; display:flex; flex-direction:column; }
.apex27-related-card img { width:100%; height:150px; object-fit:cover; border-top-left-radius:0.75rem; border-top-right-radius:0.75rem; }
.apex27-related-card .body { padding:0.75rem 0.85rem 1rem; font-size:0.875rem; }
.apex27-related-card .price { font-weight:600; font-size:1rem; color:#00665a; }
.apex27-pills { display:flex; flex-wrap:wrap; gap:.5rem; margin:0; padding:0; list-style:none; }
.apex27-pills li { background:#f1f3f5; border-radius:2rem; padding:.35rem .85rem; font-size:.75rem; font-weight:600; letter-spacing:.5px; text-transform:uppercase; }
.apex27-panels { display:grid; gap:1.25rem; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); }
.apex27-panel { background:#f8f9fa; border:1px solid #e3e6e9; border-radius:0.75rem; padding:1.25rem 1.25rem 1.6rem; position:relative; }
.apex27-panel h4 { font-size:1rem; font-weight:700; margin:0 0 .75rem; }
.apex27-panel.small { padding:1rem; }
.apex27-metric { font-size:2rem; font-weight:700; color:#00665a; }
.apex27-metric-sub { font-size:.75rem; text-transform:uppercase; letter-spacing:.5px; color:#495057; }
.apex27-grid-cols-3 { display:grid; gap:1.25rem; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); }
.apex27-mini-list { list-style:none; margin:0; padding:0; font-size:.8125rem; }
.apex27-mini-list li { margin-bottom:.35rem; }
@media (max-width: 767px){ .apex27-related-card { flex:0 0 70%; } }
</style>

<?php
// Helper: fetch related / similar properties (best-effort; depends on plugin API)
$related_properties = [];
try {
        if (method_exists($apex27, 'search_properties') && isset($details->bedrooms)) {
                $search_args = [
                        'limit' => 6,
                        'min_beds' => max(1, (int)$details->bedrooms - 1),
                        'max_beds' => (int)$details->bedrooms + 1,
                ];
                if (!empty($details->latitude) && !empty($details->longitude)) {
                        $search_args['near'] = $details->latitude . ',' . $details->longitude;
                }
                $related = $apex27->search_properties($search_args);
                if (is_array($related)) {
                        // Exclude current property by id if id available
                        foreach ($related as $r) { if (!isset($details->id) || !isset($r->id) || $r->id != $details->id) { $related_properties[] = $r; } }
                }
        }
} catch (Throwable $e) { /* silently ignore */ }

// Helper: derive local intelligence placeholders
$intelligence = [
        'tenants' => isset($details->bedrooms) ? max(50, (int)$details->bedrooms * 120) : 0,
        'properties' => isset($details->bedrooms) ? 1000 + (int)$details->bedrooms * 10 : 1000,
];

// Nearest stations placeholder (requires coords – we provide a best-effort static fallback)
$nearest_stations = [];
if (!empty($details->nearestStations) && is_array($details->nearestStations)) {
        $nearest_stations = $details->nearestStations; // Assume plugin may supply
} elseif (!empty($details->displayAddress)) {
        // Minimal fallback examples
        $nearest_stations = [
                (object)['name' => __('Central Station','apex27'), 'distance' => '0.5 mi'],
                (object)['name' => __('Park Station','apex27'), 'distance' => '0.8 mi'],
        ];
}
?>

<div class="apex27-section alt">
    <div class="container">
        <div class="apex27-cta-box">
            <h2 class="mb-3" dir="auto"><?=htmlspecialchars(__('Interested in this property?', $text_domain))?></h2>
            <p class="lead mb-4" dir="auto"><?=htmlspecialchars(__('Call our team now to arrange a viewing or ask a question.', $text_domain))?></p>
            
        </div>
    </div>
</div>

<?php if ($related_properties) { ?>
<div class="apex27-section" id="apex27-related">
    <div class="container">
        <h3 class="mb-4" dir="auto"><?=htmlspecialchars(__('You might also be interested in', $text_domain))?></h3>
        <div class="apex27-related">
            <?php foreach($related_properties as $rel) { 
                    $rImgs = $rel->images ?? [];
                    $thumb = '';
                    if ($rImgs) { $thumb = htmlspecialchars($rImgs[0]->url); }
                    $relUrl = isset($rel->permalink) ? $rel->permalink : (isset($rel->id) ? add_query_arg('property_id', $rel->id, site_url('/property/')) : '#');
                    ?>
                    <article class="apex27-related-card">
                        <?php if($thumb) { ?><a href="<?=esc_url($relUrl)?>"><img src="<?=$thumb?>" alt="<?=htmlspecialchars($rel->displayAddress ?? __('Property', $text_domain))?>"></a><?php } ?>
                        <div class="body">
                            <div class="price"><?=htmlspecialchars($rel->displayPrice ?? '')?></div>
                            <div class="mb-1" style="font-weight:600; line-height:1.2;">
                                <a href="<?=esc_url($relUrl)?>" style="text-decoration:none; color:#212529;"><?=htmlspecialchars($rel->displayAddress ?? '')?></a>
                            </div>
                            <ul class="apex27-pills mb-2">
                                <?php if(isset($rel->bedrooms)) { ?><li><?=intval($rel->bedrooms)?> <?=__('Beds',$text_domain)?></li><?php } ?>
                                <?php if(isset($rel->bathrooms)) { ?><li><?=intval($rel->bathrooms)?> <?=__('Baths',$text_domain)?></li><?php } ?>
                                <?php if(isset($rel->livingRooms)) { ?><li><?=intval($rel->livingRooms)?> <?=__('Living',$text_domain)?></li><?php } ?>
                            </ul>
                            <div style="font-size:.7rem; text-transform:uppercase; letter-spacing:.5px; color:#6c757d;">
                                <?=htmlspecialchars($rel->propertyType ?? '')?>
                            </div>
                        </div>
                    </article>
            <?php } ?>
        </div>
    </div>
</div>
<?php } ?>

<div class="apex27-section" id="apex27-local-intelligence">
    <div class="container">
        <h3 class="mb-4" dir="auto"><?=htmlspecialchars(__('Local Intelligence', $text_domain))?></h3>
        <div class="apex27-panels mb-4">
            <div class="apex27-panel">
                <h4><?=htmlspecialchars(__('Tenant Demand (indicative)', $text_domain))?></h4>
                <div class="apex27-metric"><?=number_format($intelligence['tenants'])?></div>
                <div class="apex27-metric-sub"><?=htmlspecialchars(__('Active Seeker Signals', $text_domain))?></div>
            </div>
            <div class="apex27-panel">
                <h4><?=htmlspecialchars(__('Properties in Area (est.)', $text_domain))?></h4>
                <div class="apex27-metric"><?=number_format($intelligence['properties'])?>+</div>
                <div class="apex27-metric-sub"><?=htmlspecialchars(__('Residential Units', $text_domain))?></div>
            </div>
            <div class="apex27-panel">
                <h4><?=htmlspecialchars(__('How is the market performing?', $text_domain))?></h4>
                <p class="mb-2" style="font-size:.85rem;"><?=htmlspecialchars(__('Stable with moderate buyer enquiry week-on-week.', $text_domain))?></p>
                <span class="badge bg-success"><?=htmlspecialchars(__('Stable', $text_domain))?></span>
            </div>
            <div class="apex27-panel">
                <h4><?=htmlspecialchars(__('What could your property be worth?', $text_domain))?></h4>
                <p class="mb-3" style="font-size:.85rem;"><?=htmlspecialchars(__('Request a free, no obligation valuation from our local expert.', $text_domain))?></p>
                
            </div>
        </div>
        <div class="apex27-grid-cols-3">
            <div>
                <h5 class="fw-bold mb-2" style="font-size:.9rem; text-transform:uppercase; letter-spacing:.5px;"><?=htmlspecialchars(__('Nearest Stations', $text_domain))?></h5>
                <ul class="apex27-mini-list">
                    <?php foreach($nearest_stations as $st) { ?>
                        <li><?=htmlspecialchars(($st->name ?? 'Station'). (isset($st->distance)? ' • '.$st->distance : ''))?></li>
                    <?php } ?>
                    <?php if (!$nearest_stations) { ?><li><?=htmlspecialchars(__('Data unavailable', $text_domain))?></li><?php } ?>
                </ul>
            </div>
            <div>
                <h5 class="fw-bold mb-2" style="font-size:.9rem; text-transform:uppercase; letter-spacing:.5px;"><?=htmlspecialchars(__('Market Review', $text_domain))?></h5>
                <ul class="apex27-mini-list">
                    <li><?=htmlspecialchars(__('Average time to let: 28 days', $text_domain))?></li>
                    <li><?=htmlspecialchars(__('Average price change (12m): +2.1%', $text_domain))?></li>
                    <li><?=htmlspecialchars(__('Rental yield (gross est.): 3.9%', $text_domain))?></li>
                </ul>
            </div>
            <div>
                <h5 class="fw-bold mb-2" style="font-size:.9rem; text-transform:uppercase; letter-spacing:.5px;"><?=htmlspecialchars(__('Local Office', $text_domain))?></h5>
                <p style="font-size:.8rem;" class="mb-2">
                    <?=htmlspecialchars(get_bloginfo('name'))?><br>
                    <?=htmlspecialchars(__('Local Property Team', $text_domain))?><br>
                    <a href="tel:<?=preg_replace('/[^0-9+]/','', get_option('admin_phone', '+440000000000'))?>" style="text-decoration:none;">Call <?=htmlspecialchars(get_option('admin_phone','+44 00 0000 0000'))?></a>
                </p>
                
            </div>
        </div>
    </div>
</div>

<script>
// Horizontal scroll drag for related properties (optional UX sugar)
(function(){
    var scroller = document.querySelector('.apex27-related');
    if(!scroller) return;
    var isDown=false,startX,scrollLeft;
    scroller.addEventListener('mousedown',function(e){ isDown=true; scroller.classList.add('dragging'); startX=e.pageX-scroller.offsetLeft; scrollLeft=scroller.scrollLeft; });
    ['mouseleave','mouseup'].forEach(function(ev){ scroller.addEventListener(ev,function(){ isDown=false; scroller.classList.remove('dragging'); }); });
    scroller.addEventListener('mousemove',function(e){ if(!isDown) return; e.preventDefault(); var x=e.pageX-scroller.offsetLeft; var walk=(x-startX)*1; scroller.scrollLeft=scrollLeft-walk; });
})();
</script>
<?php
get_footer();
