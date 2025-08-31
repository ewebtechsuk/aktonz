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
$form_path = $apex27->get_template_path("enquiry-form");
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
.property-main-image {
    width: 100%;
    height: 400px;
    object-fit: cover;
    border-radius: 1rem;
    margin-bottom: 1rem;
    background: #f8f9fa;
    transition: opacity 0.2s;
}
.property-thumbnails {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    justify-content: flex-start;
}
.property-thumbnail {
    width: 80px;
    height: 60px;
    object-fit: cover;
    border-radius: 0.5rem;
    border: 2px solid transparent;
    cursor: pointer;
    transition: border 0.2s;
}
.property-thumbnail.active,
.property-thumbnail:focus {
    border: 2px solid #007bff;
}
@media (max-width: 991px) {
    .property-main-image { height: 250px; }
    .property-thumbnail { width: 60px; height: 45px; }
}
@media (max-width: 575px) {
    .property-main-image { height: 180px; }
    .property-thumbnail { width: 44px; height: 33px; }
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
@media (max-width: 991px) {
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
                <a href="#property-details-contact-form" class="btn btn-lg btn-brand me-2 mb-2">
                    <i class="fa fa-envelope"></i> Make Enquiry
                </a>
                <button class="btn btn-lg btn-warning mb-2" onclick="showViewingForm(); return false;">
                    <i class="fa fa-calendar-check"></i> Book Viewing
                </button>
                <button class="btn btn-lg btn-primary mb-2" onclick="showOfferForm(); return false;">
                    <i class="fa fa-hand-holding-usd"></i> Make Offer
                </button>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-8">
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
                        <img id="mainPropertyImage" class="property-main-image" src="<?=htmlspecialchars($property_images[0]->url)?>" alt="<?=htmlspecialchars($property_images[0]->caption ?? $details->displayAddress)?>" />
                        <div class="property-thumbnails mt-2">
                            <?php foreach($property_images as $idx => $img) { ?>
                                <img class="property-thumbnail<?=$idx === 0 ? ' active' : ''?>" src="<?=htmlspecialchars($img->url)?>" alt="<?=htmlspecialchars($img->caption ?? $details->displayAddress)?>" data-full="<?=htmlspecialchars($img->url)?>" data-idx="<?=$idx?>" tabindex="0" />
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
                <div class="property-description">
                    <h4 class="text-brand mb-3" dir="auto"><?=htmlspecialchars(__("Description", $text_domain))?></h4>
                    <?php if($details->description) { ?>
                        <p dir="auto" class="mb-2"><?=htmlspecialchars($description_line_1)?></p>
                        <?php foreach($description_lines as $line) { ?>
                            <p dir="auto" class="mb-2"><?=$apex27->format_text($line)?></p>
                        <?php } ?>
                    <?php } else { ?>
                        <p dir="auto"><em><?=htmlspecialchars(__("No description is available for this property.", $text_domain))?></em></p>
                    <?php } ?>
                </div>
                <div class="row g-3 mb-4">
                    <?php if($details->epcs) { ?>
                    <div class="col-12 col-md-6">
                        <a href="#property-details-epcs" class="btn btn-outline-brand w-100" data-type="epcs">
                            <i class="fa fa-bolt"></i> <?=htmlspecialchars(__("EPC", $text_domain))?>
                        </a>
                    </div>
                    <?php } ?>
                    <?php if($details->brochures) {
                        $brochure_count = count($details->brochures);
                        if($brochure_count > 1) { ?>
                        <div class="col-12 col-md-6">
                            <a href="#property-details-brochures" class="btn btn-outline-brand w-100" data-type="brochures">
                                <i class="fa fa-file-pdf"></i> <?=htmlspecialchars(__("Brochures", $text_domain))?>
                            </a>
                        </div>
                        <?php } else {
                            $brochure = $details->brochures[0]; ?>
                        <div class="col-12 col-md-6">
                            <a href="<?=htmlspecialchars($brochure->url)?>" class="btn btn-outline-brand w-100" target="_blank">
                                <i class="fa fa-file-pdf"></i> <?=htmlspecialchars(__("Brochure", $text_domain))?>
                            </a>
                        </div>
                        <?php }
                    } ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="sticky-sidebar">
                    <?php if($details->bullets) { ?>
                    <div class="mb-4">
                        <h3 class="text-brand mt-0 mb-3" dir="auto"><i class="fa fa-star text-warning"></i> <?=htmlspecialchars(__("Key Features", $text_domain))?></h3>
                        <ul class="property-features-list">
                            <?php foreach($details->bullets as $bullet) { ?>
                                <li dir="auto">
                                    <i class="fa fa-check-circle text-success"></i>
                                    <span class="bullet-text"> <?=htmlspecialchars($bullet)?></span>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                    <?php } ?>
                    <div class="mb-4">
                        <?php $apex27->include_template($form_path, ["property_details" => $details]); ?>
                    </div>
                </div>
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
<!-- Modal for Make Offer -->
<div id="offer-form-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeOfferForm();">&times;</span>
        <?php echo do_shortcode('[offrz_offer_form]'); ?>
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
// Property image thumbnail click handler
(function() {
    var mainImg = document.getElementById('mainPropertyImage');
    var thumbs = document.querySelectorAll('.property-thumbnail');
    thumbs.forEach(function(thumb) {
        thumb.addEventListener('click', function() {
            mainImg.src = this.getAttribute('data-full');
            thumbs.forEach(function(t) { t.classList.remove('active'); });
            this.classList.add('active');
        });
        thumb.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });
})();
</script>
<!-- If using Bootstrap 5, ensure JS is loaded for carousel -->
<?php
get_footer();
