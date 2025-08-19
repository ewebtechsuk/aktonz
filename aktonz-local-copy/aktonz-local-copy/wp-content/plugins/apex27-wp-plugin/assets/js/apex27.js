class Apex27 {

	constructor() {
		this.initFsLightbox();
		this.initSearchForm();
		this.initSlider();
		this.initContactForm();
		this.initMediaButtons();
		this.initSearchResultsMap();
		this.initMakeOfferButton();  // Added to initialize the "Make Offer" button
	}

	initFsLightbox() {
		refreshFsLightbox();
	}

	initSearchForm() {

		const $ = jQuery;

		const $form = $("#property-search-form");

		const $typeDropdown = $("#property-search-type");
		const $minPriceDropdown = $("#property-search-min-price");
		const $maxPriceDropdown = $("#property-search-max-price");

		$typeDropdown.on("change", () => {

			const value = $typeDropdown.val();

			const salePriceOptions = $form.data("sale-price-options");
			const rentPriceOptions = $form.data("rent-price-options");

			const options = value === "rent" ? rentPriceOptions : salePriceOptions;

			const firstValue = parseInt(options[0].value);
			const firstValueInDropdown = parseInt($minPriceDropdown.find("option[value!='']").get(0).value);

			if(firstValue === firstValueInDropdown) return;

			$minPriceDropdown.find("option[value!='']").remove();
			$maxPriceDropdown.find("option[value!='']").remove();

			options.forEach(option => {
				const optionHtml = `<option value="${option.value}">${option.display}</option>`;

				$minPriceDropdown.append(optionHtml);
				$maxPriceDropdown.append(optionHtml);
			});


		});

	}

	initSlider() {
		this.thumbnailImgs = document.querySelectorAll("#property-details-thumbnails img");
		this.thumbnailImgs.forEach((img, index) => {
			img.addEventListener("click", event => {
				this.showImage(index);
			});
		});

		this.thumbnailLinks = document.querySelectorAll("#property-details-thumbnails a");

		this.sliderMainContainer = document.querySelector("#property-details-slider-main-container");

		this.currentImageIndex = 0;

		this.sliderPrev = document.getElementById("property-details-slider-prev");
		if(this.sliderPrev) {
			this.sliderPrev.addEventListener("click", event => {
				event.preventDefault();
				this.showPrevImage();
			});
		}

		this.sliderNext = document.getElementById("property-details-slider-next");
		if(this.sliderNext) {
			this.sliderNext.addEventListener("click", event => {
				event.preventDefault();
				this.showNextImage();
			});
		}

		this.showImage(0);
	}

	initContactForm() {
		this.contactForm = document.getElementById("apex27-contact-form");
		if(this.contactForm) {
			this.contactForm.addEventListener("submit", event => this.contact(event));
		}
	}

	initMediaButtons() {
		const $ = jQuery;
		const $mediaButtons = $(".apex27-container").find(".btn-media");
		$mediaButtons.on("click", event => {
			const $button = $(event.currentTarget);
			const type = $button.data("type");
			if(fsLightboxInstances[type]) {
				event.preventDefault();
				event.stopImmediatePropagation();
				fsLightboxInstances[type].open(0);
			}
		});
	}

	initMakeOfferButton() {
		const makeOfferButton = document.querySelector(".btn-make-offer");
		const offerModal = document.getElementById("offer-form-modal");
		const closeModal = document.querySelector(".modal .close");

		if (makeOfferButton && offerModal) {
			makeOfferButton.addEventListener("click", (event) => {
				event.preventDefault();
				offerModal.style.display = "block";
			});
		}

		if (closeModal) {
			closeModal.addEventListener("click", () => {
				offerModal.style.display = "none";
			});
		}

		// Close the modal if the user clicks outside of it
		window.addEventListener("click", (event) => {
			if (event.target === offerModal) {
				offerModal.style.display = "none";
			}
		});
	}

	showNextImage() {
		const maxIndex = this.thumbnailImgs.length - 1;
		const index = (maxIndex === this.currentImageIndex) ? 0 : this.currentImageIndex + 1;
		this.showImage(index);
	}

	showPrevImage() {
		const maxIndex = this.thumbnailImgs.length - 1;
		const index = (this.currentImageIndex === 0) ? maxIndex : this.currentImageIndex - 1;
		this.showImage(index);
	}

	showImage(index) {
		const currentThumbnailImg = this.thumbnailImgs[this.currentImageIndex];
		if(typeof currentThumbnailImg === "undefined") return;

		currentThumbnailImg.classList.remove("active");

		const img = this.thumbnailImgs[index];

		this.sliderMainContainer.innerHTML = "";

		const type = img.dataset.type;

		let element = null;

		if(type === "image") {
			element = document.createElement("img");
			element.src = img.src;

			element.addEventListener("click", event => {
				fsLightboxInstances["slider"].open(this.currentImageIndex);
			});
		}
		if(type === "video") {
			const url = img.dataset.url;
			const youTubeId = this.getYouTubeId(url);
			if(youTubeId) {
				element = document.createElement("iframe");
				element.width = "100%";
				element.height = "500";
				element.src = `https://www.youtube.com/embed/${youTubeId}`;
				element.title = "YouTube video player";
				element.frameBorder = "0";
				element.allow = "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture";
				element.allowFullscreen = true;
			}
			else {
				element = document.createElement("video");
				element.src = img.dataset.url;
				element.controls = true;
			}
		}

		if(element) {
			this.sliderMainContainer.appendChild(element);
		}

		this.currentImageIndex = index;
		img.classList.add("active");
		this.updateThumbnailScroll(img);
	}

	getYouTubeId(url) {
		const pattern = /^.*(youtu\.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=|\?v=)([^#&?]*).*/;
		const match = url.match(pattern);
		if(match && match[2].length === 11) {
			return match[2];
		}

		return null;
	}

	updateThumbnailScroll(img) {
		const $ = jQuery;

		const $img = $(img);
		const $container = $img.parent();

		const parentWidth = img.parentNode.offsetWidth;
		const parentScrollLeft = img.parentNode.scrollLeft;

		const position = $img.position();

		const offset = position.left + parentScrollLeft;
		const imgWidth = img.offsetWidth;

		const imgMaxOffset = offset + imgWidth;
		const scrollMaxOffset = parentScrollLeft + parentWidth;

		const imgOverflowsRight = (imgMaxOffset > scrollMaxOffset);
		const imgOverflowsLeft = (offset < parentScrollLeft);

		if(imgOverflowsRight) {
			const difference = imgMaxOffset - scrollMaxOffset;
			const scrollLeft = parentScrollLeft + (difference + (imgWidth / 2));
			$container.animate({scrollLeft}, 250);
		}

		if(imgOverflowsLeft) {
			const difference = parentScrollLeft - offset;
			const scrollLeft = parentScrollLeft - (difference + (imgWidth / 2));
			$container.animate({scrollLeft}, 250);
		}

	}

	contact(event) {
		event.preventDefault();

		const $form = jQuery(this.contactForm);

		const $submitButton = $form.find("[type=submit]");
		const $result = jQuery("#apex27-contact-form-result");

		const action = this.contactForm.action;
		const data = $form.serialize();

		$result.text("");
		$submitButton.prop("disabled", true);
		$submitButton.text("Submitting...");

		jQuery.post(action, data).always(() => {
			$submitButton.prop("disabled", false);
			$submitButton.text("Submit");
		}).then(
			response => {
				if(!response.success) {
					// Error
					$result.css("color", "red");
					if(response.data.message) {
						$result.text(response.data.message);
					}
					else {
						$result.text("Your enquiry could not be sent. Please try again later.");
					}
					return;
				}

				$form.slideUp("fast");

				$result.css("color", "green");
				$result.text("Thank you. Your enquiry has been sent successfully.");

			},
			() => {
				$result.css("color", "red");
				$result.text("Your enquiry could not be sent. Please check your network connectivity and try again.");
			}
		)
	}

	initSearchResultsMap() {
		// ... keep the initSearchResultsMap as it is ...
	}
}

document.addEventListener("DOMContentLoaded", () => {
	new Apex27();
});
