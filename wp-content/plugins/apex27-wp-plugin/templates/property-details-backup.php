<?php

/**

 * Template Name: Property Details

 */



/**

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



$generate_media_section = static function($title, $id, $items) {



	$images = [];

	$others = [];



	foreach($items as $item) {

		if($item->type === "image") {

			$images[] = $item;

		}

		else {

			$others[] = $item;

		}

	}



	foreach($images as $item) {

		?>

		<a href="<?=htmlspecialchars($item->url)?>" data-fslightbox="<?=htmlspecialchars($id)?>" data-type="image" style="display: none;">

			<?=htmlspecialchars($item->name)?>

		</a>

		<?php

	}



	if($others) {

		?>

		<h4 class="text-brand" id="property-details-<?=htmlspecialchars($id)?>"><?=htmlspecialchars($title)?></h4>

		<ul class="apex27-media-list">

			<?php

			foreach($others as $item) {

				?>

				<li>

					<a href="<?=htmlspecialchars($item->url)?>" target="_blank">

						<?=htmlspecialchars($item->name)?>

					</a>

				</li>

				<?php

			}

			?>

		</ul>

		<?php

	}

};



$referer = !empty($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : null;

$show_back = strpos($referer, "search") !== false;



?>



<div class="apex27-container" data-featured="<?=json_encode($featured)?>">



	<div class="mb-3" dir="auto">

		<h2 class="mb-3" dir="auto">

			<?=htmlspecialchars($details->displayAddress)?>



			<?php

			if($details->imageOverlayText) {

				?>

				<span class="property-details-status">

					<?=htmlspecialchars($details->imageOverlayText)?>

				</span>

				<?php

			}

			?>

		</h2>



		<?php

		if(!$details->isCommercial) {

			?>

			<p class="text-brand mb-3">

				<span title="Bedrooms"><i class="fa fa-fw fa-bed"></i> <?=htmlspecialchars($details->bedrooms)?></span>

				<span title="Bathrooms"><i class="fa fa-fw fa-bath"></i> <?=htmlspecialchars($details->bathrooms)?></span>

				<span title="Living Rooms"><i class="fa fa-fw fa-couch"></i> <?=htmlspecialchars($details->livingRooms)?></span>

			</p>

			<?php

		}

		?>



		<span class="lead text-brand">

			<?=htmlspecialchars($details->displayPrice)?>

			<small><?=htmlspecialchars($details->pricePrefix)?></small>

		</span>



		<strong>

			<?=htmlspecialchars($details->subtitle)?>

		</strong>

	</div>



	<div class="row">

		<div class="col-xl-8">

			<?php

			if($details->images) {

				$image = $details->images[0];

				?>

				<div id="property-details-slider">

					<?php

					$thumbnail_url = $image->thumbnailUrl;

					$caption = $image->caption;

					?>

					<div id="property-details-slider-main-container">

						<img src="<?=htmlspecialchars($thumbnail_url)?>" alt="<?=htmlspecialchars($caption)?>" />

					</div>



					<a id="property-details-slider-prev" href="#">

						<i class="fa fa-arrow-left"></i>



					</a>

					<a id="property-details-slider-next" href="#">

						<i class="fa fa-arrow-right"></i>

					</a>



					<?php

					if($details->imageOverlayText) {

						?>

						<span class="property-overlay-text">

							<?=htmlspecialchars($details->imageOverlayText)?>

						</span>

						<?php

					}

					?>



				</div>



				<div id="property-details-thumbnails">



					<?php



					$index = 0;



					foreach($details->images as $image) {

						$active = $index === 0 ? "active" : "";

						$url = $image->url;

						$thumbnail_url = $image->thumbnailUrl;

						$caption = $image->caption;

						?>

						<img class="<?=$active?>" src="<?=htmlspecialchars($thumbnail_url)?>" alt="<?=htmlspecialchars($caption)?>" data-type="image" />

						<a data-fslightbox="slider" data-type="image" href="<?=htmlspecialchars($url)?>"></a>

						<?php

						$index++;

					}



					foreach($details->videos as $index => $video) {

						$active = $index === 0 ? "active" : "";

						$url = $video->url;

						$thumbnail_url = $apex27->get_plugin_url() . "assets/img/video.png";

						$caption = "Video";

						?>

						<img class="<?=$active?>" src="<?=htmlspecialchars($thumbnail_url)?>" alt="<?=htmlspecialchars($caption)?>" data-type="video" data-url="<?=htmlspecialchars($url)?>" />

						<a data-fslightbox="slider" data-type="video" href="<?=htmlspecialchars($url)?>"></a>

						<?php

						$index++;

					}



					?>



				</div>

				<?php

			}

			?>



			<div id="property-details-media-buttons" class="mb-5" dir="auto">

				<?php

				// Buttons

				if($details->floorplans) {

					?>

					<a href="#property-details-floorplans" class="btn btn-brand btn-media" data-type="floorplans">

						<?=htmlspecialchars(__("Floorplans", $text_domain))?>

					</a>

					<?php

				}



				if($details->epcs) {

					?>

					<a href="#property-details-epcs" class="btn btn-brand btn-media" data-type="epcs">

						<?=htmlspecialchars(__("EPC", $text_domain))?>

					</a>

					<?php

				}



				if($details->brochures) {

					$brochure_count = count($details->brochures);

					if($brochure_count > 1) {

						?>

						<a href="#property-details-brochures" class="btn btn-brand btn-media" data-type="brochures">

							<?=htmlspecialchars(__("Brochures", $text_domain))?>

						</a>

						<?php

					}

					else {

						$brochure = $details->brochures[0];

						?>

						<a href="<?=htmlspecialchars($brochure->url)?>" class="btn btn-brand" target="_blank">

							<?=htmlspecialchars(__("Brochure", $text_domain))?>

						</a>

						<?php

					}

				}



				if($details->videos) {

					$video_count = count($details->videos);

					if($video_count > 1) {

						?>

						<a href="#property-details-videos" class="btn btn-brand">

							<?=htmlspecialchars(__("Videos", $text_domain))?>

						</a>

						<?php

					}

					else {

						$video = $details->videos[0];

						?>

						<a href="<?=htmlspecialchars($video->url)?>" class="btn btn-brand" target="_blank">

							<?=htmlspecialchars(__("Video", $text_domain))?>

						</a>

						<?php

					}

				}



				if($details->virtualTours) {

					$virtual_tour_count = count($details->virtualTours);

					if($virtual_tour_count > 1) {

						?>

						<a href="#property-details-virtual-tours" class="btn btn-brand">

							<?=htmlspecialchars(__("Virtual Tours", $text_domain))?>

						</a>

						<?php

					}

					else {

						$virtual_tour = $details->virtualTours[0];

						?>

						<a href="<?=htmlspecialchars($virtual_tour->url)?>" class="btn btn-brand" target="_blank">

							<?=htmlspecialchars(__("Virtual Tour", $text_domain))?>

						</a>

						<?php

					}

				}



				if($options->hasGoogleApiKey) {

					if($details->hasGeolocation) {

						?>

						<a href="#property-details-map" class="btn btn-brand">

							<?=htmlspecialchars(__("Map", $text_domain))?>

						</a>

						<?php

					}

					if($details->hasPov) {

						?>

						<a href="#property-details-street-view" class="btn btn-brand">

							<?=htmlspecialchars(__("Street View", $text_domain))?>

						</a>

						<?php

					}

				}



				?>



				<a href="#property-details-contact-form" class="btn btn-brand">

					<?=htmlspecialchars(__("Make Enquiry", $text_domain))?>

				</a>

				<a href="#" class="btn btn-brand" onclick="showOfferForm(); return false;">
   				<?=htmlspecialchars(__("Make Offer", $text_domain))?>
				</a>




				<?php

				if($show_back) {

					?>

					<a href="<?=htmlspecialchars($referer)?>" class="btn btn-brand">

						<?=htmlspecialchars(__("Back to Search Results", $text_domain))?>

					</a>

					<?php

				}

				?>



			</div>



			<h4 class="text-brand" dir="auto"><?=htmlspecialchars(__("Description", $text_domain))?></h4>



			<?php

			if($details->description) {

				?>

				<div class="mb-3 text-brand">

					<p dir="auto">

						<?=htmlspecialchars($description_line_1)?>

					</p>

				</div>



				<div class="mb-5">

					<?php

					foreach($description_lines as $line) {

						?>

						<p dir="auto"><?=$apex27->format_text($line)?></p>

						<?php

					}

					?>

				</div>

				<?php

			}

			else {

				?>

				<p dir="auto"><em><?=htmlspecialchars(__("No description is available for this property.", $text_domain))?></em></p>

				<?php

			}



			if($details->grossYield) {

				?>

				<p dir="auto">

					<?=htmlspecialchars(__("Gross Yield", $text_domain))?>: <?=htmlspecialchars($details->grossYield)?>

				</p>

				<?php

			}



			if($details->rooms) {

				foreach($details->rooms as $room) {

					?>

					<div class="mb-5">

						<div class="text-brand" dir="auto">

							<strong><?=htmlspecialchars($room->name)?></strong>

						</div>

						<?php

						if($room->dimensions) {

							?>

							<div style="opacity: 0.65; font-size: 16px;" dir="auto">

								<em><?=htmlspecialchars($room->dimensions)?> (<?=htmlspecialchars($room->feetInches)?>)</em>

							</div>

							<?php

						}

						?>

						<div class="mt-1" dir="auto">

							<?=htmlspecialchars($room->description)?>

						</div>

					</div>

					<?php

				}

			}



			?>



			<div class="mb-5" dir="auto">

				<small><?=htmlspecialchars(__("Reference", $text_domain))?>: <?=htmlspecialchars($details->reference)?></small>

			</div>



			<?php

			if($details->disclaimer) {

				?>

				<div class="mb-5" dir="auto">

					<small>

						<?=htmlspecialchars($details->disclaimer)?>

					</small>

				</div>

				<?php

			}



			if($details->additionalDetails) {

				?>

				<h4 class="text-brand" dir="auto"><?=htmlspecialchars(__("Additional Details", $text_domain))?></h4>

				<ul>

					<?php

					foreach($details->additionalDetails as $detail) {

						$label = $detail->label;

						$text = $detail->text;

						?>

						<li dir="auto">

							<strong><?=htmlspecialchars($label)?>:</strong>

							<?=htmlspecialchars($text)?>

						</li>

						<?php

					}

					?>

				</ul>

				<?php

			}



			if($details->additionalFeatures) {

				?>

				<h4 class="text-brand" dir="auto"><?=htmlspecialchars(__("Additional Features", $text_domain))?></h4>

				<ul>

					<?php

					foreach($details->additionalFeatures as $feature) {

						?>

						<li dir="auto"><?=htmlspecialchars($feature)?></li>

						<?php

					}

					?>

				</ul>

				<?php

			}



			if($details->floorplans) {

				$generate_media_section(__("Floorplans", $text_domain), "floorplans", $details->floorplans);

			}



			if($details->epcs) {

				$generate_media_section(__("EPCs", $text_domain), "epcs", $details->epcs);

			}



			if($details->brochures) {

				$generate_media_section(__("Brochures", $text_domain), "brochures", $details->brochures);

			}



			if($details->videos) {

				$generate_media_section(__("Videos", $text_domain), "videos", $details->videos);

			}



			if($details->virtualTours) {

				?>

				<h4 class="text-brand" id="property-details-virtual-tours" dir="auto"><?=htmlspecialchars(__("Virtual Tours", $text_domain))?></h4>

				<ul>

					<?php

					foreach($details->virtualTours as $tour) {

						?>

						<li dir="auto">

							<a href="<?=htmlspecialchars($tour->url)?>" target="_blank">

								<?=htmlspecialchars(__("View Virtual Tour", $text_domain))?>

							</a>

						</li>

						<?php

					}

					?>

				</ul>

				<?php

			}



			if($options->hasGoogleApiKey) {

				if($details->hasGeolocation) {

					?>

					<h4 class="text-brand" dir="auto"><?=htmlspecialchars(__("Map", $text_domain))?></h4>

					<iframe id="property-details-map" class="mb-5" style="border:0;" width="100%" height="450" src="<?=htmlspecialchars($details->mapEmbedUrl)?>" allowfullscreen></iframe>

					<?php

				}



				if($details->hasPov) {

					?>

					<h4 class="text-brand" dir="auto"><?=htmlspecialchars(__("Street View", $text_domain))?></h4>

					<iframe id="property-details-street-view" class="mb-5" style="border:0;" width="100%" height="450" src="<?=htmlspecialchars($details->streetViewEmbedUrl)?>" allowfullscreen></iframe>

					<?php

				}

			}



			?>



		</div>

		<div class="col-xl-4" dir="auto">



			<?php

			if($details->bullets) {

				?>

				<div class="mb-5">

					<h3 class="text-brand mt-xl-0" dir="auto"><?=htmlspecialchars(__("Features", $text_domain))?></h3>



					<ul class="property-details-bullets-list">

						<?php

						foreach($details->bullets as $bullet) {

							?>

							<li dir="auto">

								<span class="bullet-text">

									<?=htmlspecialchars($bullet)?>

								</span>

							</li>

							<?php

						}

						?>

					</ul>

				</div>

				<?php

			}



			$apex27->include_template($form_path, [

				"property_details" => $details

			]);



			?>

		</div>

		

	</div>



	<?php

	if($details->showLogo) {

		require $apex27->get_footer_path();

	}

	?>



</div>



<?php

echo do_shortcode('[offrz_offer_form]');


get_footer();
