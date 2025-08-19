( function ( $ ) {

	"use strict";

	var JetWooProductGallery = {

		init: function () {

			var self = JetWooProductGallery,
				widgets = {
					'jet-woo-product-gallery-grid.default': self.productGalleryGrid,
					'jet-woo-product-gallery-modern.default': self.productGalleryModern,
					'jet-woo-product-gallery-anchor-nav.default': self.productGalleryAnchorNav,
					'jet-woo-product-gallery-slider.default': self.productGallerySlider,
				};

			$.each( widgets, function( widget, callback ) {
				window.elementorFrontend.hooks.addAction( 'frontend/element_ready/' + widget, callback );
			} );

			// Re-init swiper in nested tabs.
			window.elementorFrontend.elements.$window.on('elementor/nested-tabs/activate', (event, content) => {
				const $content = $(content).find( '.elementor-widget-jet-woo-product-gallery-slider' );
				const $swiperElements = $content.find('.jet-woo-product-gallery-slider, .jet-woo-swiper-gallery-thumbs');
			
				if (!$swiperElements.length) {
					return;
				}
			
				$swiperElements.each(function () {
					const swiperInstance = this.swiper;
					if (swiperInstance) {
						swiperInstance.destroy();
					}
				});

				self.swiperDowngrade();
			
				setTimeout(() => {
					self.productGallerySlider($content);
				});
			});

			// Swiper.js library downgrade.
			$(window).on('jet-popup/render-content/ajax/success', function() {
				self.swiperDowngrade();	
			});
		
			for ( const productType of window.jetWooProductGalleryData.product_types ) {
				if ( $( '.woocommerce div.product' ).hasClass( 'product-type-' + productType ) ) {
					$(document)
						.on( 'show_variation', function ( form, variation ) {
							self.showVariationImage( variation );
						} )
						.on( 'reset_image', function ( form, variation ) {
							self.showVariationImage( variation );
						} );
				}
			}			
		},

		initBlocks: function() {
			JetPlugins.bulkBlocksInit( [
				{
					block: 'jet-gallery/gallery-anchor-nav',
					callback: JetWooProductGallery.productGalleryAnchorNav
				},
				{
					block: 'jet-gallery/gallery-grid',
					callback: JetWooProductGallery.productGalleryGrid
				},
				{
					block: 'jet-gallery/gallery-modern',
					callback: JetWooProductGallery.productGalleryModern
				},
				{
					block: 'jet-gallery/gallery-slider',
					callback: JetWooProductGallery.productGallerySlider
				},
			] );
		},

		swiperDowngrade: function() {
			if (!window.elementorFrontendConfig.experimentalFeatures.e_swiper_latest) {
				return;
			}
		
			const debugSuffix = window.elementorFrontendConfig.environmentMode.isScriptDebug ? '' : '.min';
			const isSwiperLoaded = window.elementorFrontend.utils.assetsLoader.isAssetLoaded(
				{ src: `${window.elementorFrontendConfig.urls.assets}lib/swiper/v8/swiper${debugSuffix}.js?ver=8.4.5` },
				'script'
			);
		
			if (isSwiperLoaded) {
				$('body').append(`<script src="${window.jetWooProductGalleryData.assets_path}/lib/swiper/swiper${debugSuffix}.js?ver=5.3.6"></script>`);
			}
		},

		showVariationImage: function ( variation ) {
			var $product = $(document).find( '.product' ),
				$product_gallery = $product.find( '.jet-woo-product-gallery' );

			$.each( $product_gallery, function () {

				if ( ! $( this ).is( '[data-variation-images]' ) ) {
					return;
				}

				var variation_images_data = $(this).data('variation-images'),
					$gallerySlider = $(this).find('.jet-woo-product-gallery-slider'),
					$swiperSetting = $gallerySlider.data('swiper-settings'),
					$product_img_wrap = null,
					$gallery_img = null,
					$featuredImage = $( this ).children().data( 'featured-image' ),
					gallerySettings = $( this ).data('gallery-settings'),
					index = gallerySettings.videoFirst ? 1 : 0;

				var sliderItemsCount = $gallerySlider.find( '.jet-woo-product-gallery__image-item' ).length;

				if ( $swiperSetting && $swiperSetting['loop'] && sliderItemsCount > 1 ) {
					$product_img_wrap = $(this).find('.jet-woo-product-gallery__image-item[data-swiper-slide-index = "' + index + '"]');
					$gallery_img = $(this).find('.jet-woo-swiper-control-thumbs__item[data-swiper-slide-index = "' + index + '"] img');
				} else {
					$product_img_wrap = $(this).find( '.jet-woo-product-gallery__image-item' ).eq( index );
					$gallery_img = $(this).find( '.jet-woo-swiper-control-thumbs__item' ).eq( index ).find( 'img' );
				}

				var $product_img = $product_img_wrap.find( '.wp-post-image' ),
					$product_link = $product_img_wrap.find( 'a' ).eq( 0 );

				if ( ! $featuredImage ) {
					$product_img = $product_img_wrap.find( '.wp-post-gallery' );
				}

				if ( variation && variation.image && variation.image.src && variation.image.src.length > 1 ) {
					var variation_image_data = variation_images_data[variation.image_id];

					setVariationImageAtts( variation, variation_image_data );
				} else {
					resetVariationImageAtts();
				}

				function setVariationImageAtts( variation, variation_image_data ) {
					$product_img.wc_set_variation_attr( 'src', variation_image_data.src );
					$product_img.wc_set_variation_attr( 'height', variation_image_data.src_h );
					$product_img.wc_set_variation_attr( 'width', variation_image_data.src_w );
					$product_img.wc_set_variation_attr( 'srcset', variation_image_data.srcset );
					$product_img.wc_set_variation_attr( 'sizes', variation_image_data.sizes );
					$product_img.wc_set_variation_attr( 'title', variation.image.title );
					$product_img.wc_set_variation_attr( 'data-caption', variation.image.caption );
					$product_img.wc_set_variation_attr( 'alt', variation.image.alt );
					$product_img.wc_set_variation_attr( 'data-src', variation_image_data.src );
					$product_img.wc_set_variation_attr( 'data-large_image', variation_image_data.full_src );
					$product_img.wc_set_variation_attr( 'data-large_image_width', variation_image_data.full_src_w );
					$product_img.wc_set_variation_attr( 'data-large_image_height', variation_image_data.full_src_h );

					$product_img_wrap.wc_set_variation_attr( 'data-thumb', variation_image_data.src );

					$product_link.wc_set_variation_attr( 'href', variation.image.full_src );

					$gallery_img.wc_set_variation_attr( 'src', variation.image.thumb_src );
					$gallery_img.wc_set_variation_attr( 'width', variation.image.thumb_src_w );
					$gallery_img.wc_set_variation_attr( 'height', variation.image.thumb_src_h );
					$gallery_img.wc_set_variation_attr( 'srcset', '' );
				}

				function resetVariationImageAtts() {
					$product_img.wc_reset_variation_attr( 'src' );
					$product_img.wc_reset_variation_attr( 'width' );
					$product_img.wc_reset_variation_attr( 'height' );
					$product_img.wc_reset_variation_attr( 'srcset' );
					$product_img.wc_reset_variation_attr( 'sizes' );
					$product_img.wc_reset_variation_attr( 'title' );
					$product_img.wc_reset_variation_attr( 'data-caption' );
					$product_img.wc_reset_variation_attr( 'alt' );
					$product_img.wc_reset_variation_attr( 'data-src' );
					$product_img.wc_reset_variation_attr( 'data-large_image' );
					$product_img.wc_reset_variation_attr( 'data-large_image_width' );
					$product_img.wc_reset_variation_attr( 'data-large_image_height' );

					$product_img_wrap.wc_reset_variation_attr( 'data-thumb' );

					$product_link.wc_reset_variation_attr( 'href' );

					$gallery_img.wc_reset_variation_attr( 'src' );
					$gallery_img.wc_reset_variation_attr( 'width' );
					$gallery_img.wc_reset_variation_attr( 'height' );
				}

			} );

			$( document ).trigger( 'jet-woo-gallery-variation-image-change' );

		},

		productGallerySlider: function ( $scope ) {

			const
				swiperContainer = $scope.find( '.jet-woo-product-gallery-slider' ),
				swiperSettings = swiperContainer.data( 'swiper-settings' ),
				gallerySettings = $scope.find( '.jet-woo-product-gallery' ).data( 'gallery-settings' ) || $scope.data( 'gallery-settings' ),
				widgetSettings = JetWooProductGallery.getElementorElementSettings( $scope );

			if ( swiperContainer.find( '.jet-woo-product-gallery__image-item' ).length > 1 ) {
				let parameters = {
					slidesPerView: 1,
					touchReleaseOnEdges: true,
					...swiperSettings
				};

				delete parameters.paginationType;

				if ( swiperSettings.centeredSlides && ! $.isEmptyObject( widgetSettings ) ) {
					parameters.slidesPerView = +widgetSettings.slider_center_mode_slides || 4;
					parameters.spaceBetween = +widgetSettings.slider_center_mode_space_between ? +widgetSettings.slider_center_mode_space_between : 0;

					const breakpointsParamsKeys = {
						slidesPerView: 'slider_center_mode_slides_',
						spaceBetween: 'slider_center_mode_space_between_',
					}

					parameters.breakpoints = JetWooProductGallery.handleSwiperBreakpoints( widgetSettings, parameters, breakpointsParamsKeys );
				}

				if ( swiperSettings.showNavigation ) {
					parameters.navigation = {
						nextEl: '.jet-swiper-button-next',
						prevEl: '.jet-swiper-button-prev'
					};
				}

				if ( swiperSettings.showPagination ) {
					if ( 'thumbnails' === swiperSettings.paginationType ) {
						const
							swiperThumbContainer = $scope.find( '.jet-woo-swiper-gallery-thumbs' ),
							swiperThumbSettings = swiperContainer.data( 'swiper-thumb-settings' );

						let thumbsParameters = {
							freeMode: swiperSettings.loop,
							slidesPerView: 4,
							spaceBetween: 10,
							watchSlidesVisibility: true,
							watchSlidesProgress: true,
							...swiperThumbSettings
						}

						if ( swiperThumbSettings.showNavigation ) {
							thumbsParameters.navigation = {
								nextEl: '.jet-thumb-swiper-nav.jet-swiper-button-next',
								prevEl: '.jet-thumb-swiper-nav.jet-swiper-button-prev'
							};
						}

						if ( ! $.isEmptyObject( widgetSettings ) ) {
							thumbsParameters.slidesPerView = +widgetSettings.pagination_thumbnails_columns;
							thumbsParameters.spaceBetween = +widgetSettings.pagination_thumbnails_space_between ? +widgetSettings.pagination_thumbnails_space_between : 0;

							const breakpointsParamsKeys = {
								slidesPerView: 'pagination_thumbnails_columns_',
								spaceBetween: 'pagination_thumbnails_space_between_',
							}

							thumbsParameters.breakpoints = JetWooProductGallery.handleSwiperBreakpoints( widgetSettings, thumbsParameters, breakpointsParamsKeys );
						}

						parameters.thumbs = {
							swiper: new JetSwiper( swiperThumbContainer, thumbsParameters )
						};

						let currentDeviceSlidePerView = 0;

						if ( window.elementorFrontend && ! $.isEmptyObject( widgetSettings ) ) {
							currentDeviceSlidePerView = +widgetSettings['pagination_thumbnails_columns'];

							if ( 'desktop' !== window.elementorFrontend.getCurrentDeviceMode() ) {
								currentDeviceSlidePerView = +widgetSettings[ 'pagination_thumbnails_columns_' + window.elementorFrontend.getCurrentDeviceMode() ];
							}
						} else {
							$.each( swiperThumbSettings.breakpoints, ( key, value ) => {
								if ( $( window ).width() > key ) {
									currentDeviceSlidePerView = value.slidesPerView
								}
							} );
						}

						if ( currentDeviceSlidePerView >= swiperThumbContainer.find( '.jet-woo-swiper-control-thumbs__item:not(.swiper-slide-duplicate)' ).length ) {
							swiperThumbContainer.addClass( 'jet-woo-swiper-gallery-thumbs-no-nav' );
							swiperThumbContainer.find( '.jet-swiper-nav' ).hide();
							swiperThumbContainer.find( '.swiper-slide-duplicate' ).hide();
						}
					} else {
						parameters.pagination = {
							el: '.swiper-pagination',
							type: 'dynamic' !== swiperSettings.paginationControllerType ? swiperSettings.paginationControllerType : 'bullets',
							clickable: true,
							dynamicBullets: !! ( 'dynamic' === swiperSettings.paginationControllerType || swiperSettings.dynamicBullets ),
						}
					}
				}

				parameters.on = {
					init: function () {
						if ( gallerySettings.hasVideo && swiperSettings.loop ) {
							swiperContainer.find( '.swiper-slide-duplicate video.jet-woo-product-video-player' ).removeAttr( 'autoplay' );
						}
					},
					imagesReady: function () {
						if ( gallerySettings.hasVideo ) {
							const videoSlide = swiperContainer.find( '.jet-woo-product-gallery--with-video' );

							if ( 'self_hosted' === gallerySettings.videoType ) {
								if ( 'horizontal' === swiperSettings.direction ) {
									if ( gallerySettings.videoAutoplay && gallerySettings.videoFirst ) {
										setTimeout( function () {
											swiper.updateAutoHeight( 100 );
										}, 300 );

										if ( ! swiperSettings.autoHeight ) {
											setSelfHostedVideoStyles( videoSlide );
										}
									}

									if ( swiperSettings.autoHeight ) {
										videoSlide.on( 'click', () => {
											setTimeout( function () {
												swiper.updateAutoHeight( 100 );
											}, 300 );
										} );
									}
								} else {
									videoSlide.each( function () {
										if ( gallerySettings.videoAutoplay ) {
											setSelfHostedVideoStyles( $( this ) );
										}

										$( this ).on( 'click', () => {
											setSelfHostedVideoStyles( $( this ) );
										} );
									} );
								}
							}
						}

						if ( 'vertical' === swiperSettings.direction ) {
							let images = swiperContainer.find( '.jet-woo-product-gallery__image-item img' );

							images.each( function() {
								let $this = $( this );

								if ( $this.height() > swiperContainer.height() ) {
									$this.css( {
										'height': swiperContainer.height() + 'px',
										'width': 'auto',
									} );
								}
							} );
						}

						let variationChange = false;

						$( document ).on( 'jet-woo-gallery-variation-image-change', () => {

							let index = 0;

							if ( variationChange && gallerySettings.videoFirst ) {
								index = 1;
							}

							if ( swiperSettings.loop ) {
								swiper.slideToLoop( index, 300, true );
							} else {
								swiper.slideTo( index, 300, true );
							}

							variationChange = true;

						} );
					},
					slideChangeTransitionStart: function() {
						if ( ! gallerySettings.hasVideo || ! swiperSettings.loop ) {
							return;
						}

						const $wrapperEl = this.$wrapperEl;
						const params = this.params;

						$wrapperEl.children( ( '.' + ( params.slideClass ) + '.' + ( params.slideDuplicateClass ) ) )
							.each( function() {
								const idx = this.getAttribute('data-swiper-slide-index');
								this.innerHTML = $wrapperEl.children( '.' + params.slideClass + '[data-swiper-slide-index="' + idx + '"]:not(.' + params.slideDuplicateClass + ')' ).html();
							} );
					},
					slideChangeTransitionEnd: function() {
						if ( gallerySettings.hasVideo && swiperSettings.loop ) {
							this.slideToLoop( this.realIndex, 0, false );
						}
					}
				};

				const swiper =  new JetSwiper( swiperContainer, parameters );
			} else {
				$scope.find( '.jet-swiper-nav' ).hide();
				$scope.find( '.swiper-pagination' ).hide();
			}

			JetWooProductGallery.productGallery( $scope );

			function setSelfHostedVideoStyles ( videoSlide ) {
				if ( ! videoSlide.find( '.mejs-container' ).hasClass( 'mejs-container-fullscreen' ) ) {
					setTimeout( function() {
							if ( videoSlide.height() > swiperContainer.height() ) {
								videoSlide.find( '.mejs-controls' ).css( {
									'top': swiperContainer.height() + 'px',
									'bottom': 'auto',
									'transform': 'translateY(-100%)'
								} );
							}
					}, 300 );
				}  else {
					videoSlide.find( '.mejs-controls' ).removeAttr( 'style' );
				}
			}

		},

		productGalleryGrid: function ($scope) {
			JetWooProductGallery.productGallery($scope);
		},

		productGalleryModern: function ($scope) {
			JetWooProductGallery.productGallery($scope);
		},

		productGalleryAnchorNav: function ($scope) {
			var item = $scope.find('.jet-woo-product-gallery__image-item'),
				navItems = $scope.find('.jet-woo-product-gallery-anchor-nav-items'),
				navController = $scope.find('.jet-woo-product-gallery-anchor-nav-controller'),
				navControllerItem = navController.find('li a'),
				dataNavItems = [],
				active = 0,
				autoScroll = false,
				scrollOffset = 0,
				scrollPos = 0,
				$wpAdminBar = $('#wpadminbar');

			if ($wpAdminBar.length) {
				scrollOffset = $wpAdminBar.outerHeight();
			}

			JetWooProductGallery.productGallery($scope);

			setControllerItemsData();
			stickyNavController();

			$(window).scroll(function () {
				if (!autoScroll) {
					setControllerItemsData();
					scrollPos = $(document).scrollTop();
					setCurrentControllerItem();
				}
			});

			scrollPos = $(document).scrollTop();
			setCurrentControllerItem();

			$(navControllerItem).on('click', function () {
				setCurrentControllerItem();

				var index = $(this).data('index'),
					pos = dataNavItems[index];

				autoScroll = true;

				$(navController).find('a.current-item').removeClass('current-item');
				$(this).addClass('current-item');

				active = index;

				if ( $( this ).parents().hasClass( 'jet-popup' ) ) {
					let popupContainer = $( this ).closest( '.jet-popup__container-inner' );

					$( popupContainer ).animate({scrollTop: pos - $( popupContainer ).offset().top + 1}, 'fast', function () {
						autoScroll = false;
					});
				} else {
					$('html, body').animate({scrollTop: pos - scrollOffset + 1}, 'fast', function () {
						autoScroll = false;
					});
				}

				return false;
			});

			function setControllerItemsData() {
				$(item).each(function () {
					var id = $(this).attr('id');
					dataNavItems[id] = $(this).offset().top;
				});
			}

			function setCurrentControllerItem() {
				for (var index in dataNavItems) {
					if (scrollPos >= (dataNavItems[index] - scrollOffset)) {
						$(navController).find('a.current-item').removeClass('current-item');
						$(navController).find('a[data-index="' + index + '"]').addClass('current-item');
					}
				}
			}

			function stickyNavController() {
				var stickyActiveDown = false,
					activeSticky = false,
					bottomedOut = false;

				$(window).on('scroll', function () {
					var windowTop = $(window).scrollTop(),
						navItemsHeight = $(navItems).outerHeight(true),
						navControllerHeight = $(navController).outerHeight(true),
						navItemsTop = $(navItems).offset().top,
						navControllerTop = $(navController).offset().top,
						navItemsBottom = navItemsTop + navItemsHeight,
						navControllerBottom = navControllerTop + navControllerHeight;

					if (navItemsBottom - navControllerHeight - scrollOffset <= windowTop) {
						return;
					}

					if (activeSticky === true && bottomedOut === false) {
						$(navController).css({
							"top": (windowTop - navItemsTop + scrollOffset) + 'px'
						});
					}

					if (windowTop < navControllerTop && windowTop < navControllerBottom) {
						stickyActiveDown = false;
						activeSticky = true;
						$(navController).css({
							"top": (windowTop - navItemsTop + scrollOffset) + 'px'
						});
					}

					if (stickyActiveDown === false && windowTop > navItemsTop) {
						stickyActiveDown = true;
						activeSticky = true;
						bottomedOut = false;
					}

					if (stickyActiveDown === false && navItemsTop > windowTop) {
						stickyActiveDown = false;
						activeSticky = false;
						bottomedOut = false;
						$(navController).removeAttr("style");
					}
				});
			}
		},

		productGallery: function ($scope) {
			var id = $scope.data('id') || $scope.parent().data( 'block-id' ),
				settings = $scope.find('.jet-woo-product-gallery').data('gallery-settings') || $scope.data('gallery-settings'),
				$galleryImages = $scope.find('.jet-woo-product-gallery__image-item:not(.swiper-slide-duplicate) .jet-woo-product-gallery__image:not(.image-with-placeholder)'),
				$galleryZoomImages = $scope.find('.jet-woo-product-gallery__image--with-zoom'),
				$galleryImagesData = getImagesData(),
				$galleryPhotoSwipeTrigger = $scope.find('.jet-woo-product-gallery__trigger'),
				photoSwipeTemplate = $('.jet-woo-product-gallery-pswp')[0],
				$galleryVideoPopupTrigger = $scope.find('.jet-woo-product-video__popup-button'),
				$galleryVideoPopupOverlay = $scope.find('.jet-woo-product-video__popup-overlay'),
				$galleryVideoIframe = $scope.find('.jet-woo-product-video-iframe'),
				galleryVideoIframeSrc = $galleryVideoIframe[0] ? $galleryVideoIframe[0].src : false,
				$galleryVideoPlayer = $scope.find('.jet-woo-product-video-player'),
				$galleryVideoDefaultPlayer = $scope.find('.jet-woo-product-video-mejs-player'),
				galleryVideoDefaultPlayerControls = $galleryVideoDefaultPlayer.data('controls') || ['playpause', 'current', 'progress', 'duration', 'volume', 'fullscreen'],
				$galleryVideoOverlay = $scope.find('.jet-woo-product-video__overlay'),
				galleryVideoHasOverlay = $galleryVideoOverlay.length > 0;

			if ( settings ) {
				var galleryPhotoSwipeSettings = {
						mainClass: $scope.parent().data( 'block-id' ) ? id + '-jet-woo-product-gallery' : id ? 'jet-woo-product-gallery-' + id : '',
						captionEl: settings.caption ? settings.caption : '',
						fullscreenEl: settings.fullscreen ? settings.fullscreen : false,
						zoomEl: settings.zoom ? settings.zoom : false,
						shareEl: settings.share ? settings.share : false,
						counterEl: settings.counter ? settings.counter : false,
						arrowEl: settings.arrows ? settings.arrows : false,
						closeOnScroll: false,
						history: false
					};

				if (settings.enableGallery) {
					$galleryPhotoSwipeTrigger.on('click.JetWooProductGallery', initPhotoSwipe);

					$(document).on('jet-woo-gallery-variation-image-change', function () {
						$galleryImagesData = getImagesData();
					});
				}

				if (settings.enableZoom) {
					initZoom();
					$(document).on('jet-woo-gallery-variation-image-change', initZoom);
				}

				if (settings.hasVideo) {
					initProductVideo();
				}
			}

			$('.jet-woo-product-gallery__image-item').find('img').on('click', function (e) { e.preventDefault(); });

			function initPhotoSwipe(e) {
				e.preventDefault();

				if ($('body').hasClass('elementor-editor-active')) {
					return;
				}

				var target = $(e.target),
					hasPlaceholder = $scope.find('.jet-woo-product-gallery__image-item.featured').hasClass('no-image'),
					clickedItem = target.parents('.jet-woo-product-gallery__image-item'),
					clickedItemData = clickedItem.data( 'swiper-slide-index' ),
					index;

				if ( 'undefined' !== typeof clickedItemData ) {
					index = clickedItemData;
				} else {
					index = $(clickedItem).index();
				}

				if (hasPlaceholder || settings.videoFirst) {
					index -= 1;
				}

				galleryPhotoSwipeSettings.index = index;

				var photoSwipe = new PhotoSwipe(photoSwipeTemplate, PhotoSwipeUI_Default, $galleryImagesData, galleryPhotoSwipeSettings);

				photoSwipe.init();

			}

			function initZoom() {
				var flag = false,
					zoomSettings = {
						magnify: settings.zoomMagnify,
						touch: false
					};

				$galleryZoomImages.each(function (index, item) {
					var image = $(item).find('img'),
						galleryWidth = image.parent().width(),
						imageWidth = image.data('large_image_width');

					if (imageWidth > galleryWidth) {
						flag = true;
					}
				});

				if (flag) {
					if ('ontouchstart' in document.documentElement) {
						zoomSettings.on = 'click';
					}

					$galleryZoomImages.trigger('zoom.destroy');
					$galleryZoomImages.zoom(zoomSettings);
				}
			}

			function initProductVideo() {

				switch ( settings.videoIn ) {
					case 'content':
						if ( $galleryVideoOverlay[0] ) {
							$galleryVideoOverlay.on( 'click.JetWooProductGallery', function ( event ) {
								if ( $galleryVideoPlayer[0] ) {
									defaultPlayerStartPlay( event.target );
								}

								if ( $galleryVideoIframe[0] ) {
									iframePlayerStartPlay( event );
								}
							} );

							if ( settings.videoAutoplay && $galleryVideoIframe[0] ) {
								iframePlayerStartPlay( event );
							}
						}

						if ( $galleryVideoPlayer ) {
							$galleryVideoPlayer.each( function () {
								$( this ).on('play.JetWooProductGallery', function () {
									if ( galleryVideoHasOverlay ) {
										$galleryVideoOverlay.remove();
										galleryVideoHasOverlay = false;
									}
								} );
							} );
						}

						if ($galleryVideoDefaultPlayer[0]) {
							defaultPlayerInit();
						}
						break;
					case 'popup':
						defaultPlayerInit();
						$galleryVideoPopupTrigger.on('click.JetWooProductGallery', function (event) {
							videoPopupOpen();
						});

						$galleryVideoPopupOverlay.on('click.JetWooProductGallery', function (event) {
							videoPopupClose();
						});
						break;
				}

				function videoPopupOpen() {

					$galleryVideoPopupTrigger.siblings('.jet-woo-product-video__popup-content').addClass('jet-woo-product-video__popup--show');

					if ( $galleryVideoPlayer[0] ) {
						$galleryVideoPlayer[0].play();

						if ( ! settings.videoAutoplay ) {
							$galleryVideoPlayer[0].pause();
							$galleryVideoPlayer[0].currentTime = 0;
						}
					}

					if ($galleryVideoIframe[0]) {
						$galleryVideoIframe[0].src = galleryVideoIframeSrc;

						if ( settings.videoAutoplay ) {
							$galleryVideoIframe[0].src = $galleryVideoIframe[0].src.replace( '&autoplay=0', '&autoplay=1' );
						}
					}

				}

				function videoPopupClose() {
					$galleryVideoPopupTrigger.siblings('.jet-woo-product-video__popup-content').removeClass('jet-woo-product-video__popup--show');
					if ($galleryVideoIframe[0]) {
						$galleryVideoIframe[0].src = '';
					}
					if ($galleryVideoPlayer) {
						$galleryVideoPlayer[0].currentTime = 0;
						$galleryVideoPlayer[0].pause();
					}
				}

				function defaultPlayerInit() {
					$galleryVideoDefaultPlayer.mediaelementplayer({
						videoVolume: 'horizontal',
						hideVolumeOnTouchDevices: false,
						enableProgressTooltip: false,
						features: galleryVideoDefaultPlayerControls,
						autoplay: false,
						pauseOtherPlayers: false,
					}).load();
				}

				function defaultPlayerStartPlay( target ) {
					let $videoPlayer = '';

					if ( $( target ).hasClass( 'jet-woo-product-video__overlay' ) ) {
						$videoPlayer = $( target ).siblings().find( '.jet-woo-product-video-player' )[1];
						$( target ).remove();
					} else {
						$videoPlayer = $( target ).parents( '.jet-woo-product-video__overlay' ).siblings().find('.jet-woo-product-video-player')[1];
						$( target ).parents( '.jet-woo-product-video__overlay' ).remove();
					}

					$videoPlayer.play();

					galleryVideoHasOverlay = false;
				}

				function iframePlayerStartPlay( event ) {
					if ( ! settings.videoAutoplay ) {
						let $videoTarget = '';
						if ( $( event.target ).hasClass( 'jet-woo-product-video__overlay' ) ) {
							$videoTarget = $( event.target ).siblings().find( '.jet-woo-product-video-iframe' );
						} else {
							$videoTarget = $( event.target ).parents( '.jet-woo-product-video__overlay' ).siblings().find( '.jet-woo-product-video-iframe' );
						}

						$videoTarget[0].src = $videoTarget[0].src.replace('&autoplay=0', '&autoplay=1');
					} else {
						$galleryVideoIframe.each( function() {
							if ( $( this ).parents( '.jet-woo-product-gallery__image-item' ).hasClass( 'swiper-slide-duplicate' ) ) {
								$( this )[0].src = $( this )[0].src.replace('&autoplay=1', '&autoplay=0');
							}
						} );
					}

					$galleryVideoOverlay.remove();
					galleryVideoHasOverlay = false;
				}
			}

			function getImagesData() {
				var data = [];

				if ($galleryImages.length > 0) {
					$galleryImages.each(function (i, element) {
						var img = $(element).find('img');

						if (img.length) {
							var largeImageSrc = img.attr('data-large_image'),
								largeImageWidth = img.attr('data-large_image_width'),
								largeImageHeight = img.attr('data-large_image_height'),
								imageData = {
									src: largeImageSrc,
									w: largeImageWidth,
									h: largeImageHeight,
									title: img.attr('data-caption') ? img.attr('data-caption') : img.attr('title')
								};
							data.push(imageData);
						}
					});
				}

				return data;
			}

		},

		getElementorElementSettings: function( $scope ) {

			if ( window.elementorFrontend && window.elementorFrontend.isEditMode() && $scope.hasClass( 'elementor-element-edit-mode' ) ) {
				return JetWooProductGallery.getEditorElementSettings( $scope );
			}

			return $scope.data( 'settings' ) || {};

		},

		getEditorElementSettings: function( $scope ) {

			var modelCID = $scope.data( 'model-cid' ),
				elementData;

			if ( ! modelCID ) {
				return {};
			}

			if ( ! window.elementorFrontend.hasOwnProperty( 'config' ) ) {
				return {};
			}

			if ( ! window.elementorFrontend.config.hasOwnProperty( 'elements' ) ) {
				return {};
			}

			if ( ! window.elementorFrontend.config.elements.hasOwnProperty( 'data' ) ) {
				return {};
			}

			elementData = window.elementorFrontend.config.elements.data[ modelCID ];

			if ( ! elementData ) {
				return {};
			}

			return elementData.toJSON();

		},

		handleSwiperBreakpoints: function ( widgetSettings, swiperParameters, paramsKeys ) {

			const
				elementorBreakpoints = window.elementorFrontend.config.responsive.activeBreakpoints,
				elementorBreakpointsValues = elementorFrontend.breakpoints.getBreakpointValues(),
				defaultSlidesToShowMap = { mobile: 2, tablet: 3 };

			let lastBreakpointSlidesToShowValue = swiperParameters.slidesPerView,
				spaceBetweenSlides = 10;

			swiperParameters.breakpoints = {};

			Object.keys( elementorBreakpoints ).reverse().forEach( breakpointName => {

				const defaultSlidesToShow = defaultSlidesToShowMap[ breakpointName ] ? defaultSlidesToShowMap[ breakpointName ] : lastBreakpointSlidesToShowValue;

				spaceBetweenSlides = +widgetSettings[ paramsKeys.spaceBetween + breakpointName ] ? +widgetSettings[ paramsKeys.spaceBetween + breakpointName ] : 0;

				swiperParameters.breakpoints[ elementorBreakpoints[ breakpointName ].value ] = {
					slidesPerView: +widgetSettings[ paramsKeys.slidesPerView + breakpointName ] || defaultSlidesToShow,
					spaceBetween: spaceBetweenSlides
				};

				lastBreakpointSlidesToShowValue = +widgetSettings[ paramsKeys.slidesPerView + breakpointName ] || defaultSlidesToShow;

			} );

			Object.keys( swiperParameters.breakpoints ).forEach( breakpoint => {

				const breakpointValue = parseInt( breakpoint );

				let breakpointToUpdate;

				if ( breakpointValue === elementorBreakpoints.mobile.value || breakpointValue + 1 === elementorBreakpoints.mobile.value ) {
					breakpointToUpdate = 0;
				} else if ( elementorBreakpoints.widescreen && ( breakpointValue === elementorBreakpoints.widescreen.value || breakpointValue + 1 === elementorBreakpoints.widescreen.value ) ) {
					breakpointToUpdate = breakpointValue;
				} else {
					const currentBreakpointIndex = elementorBreakpointsValues.findIndex( elementorBreakpoint => {
						return breakpointValue === elementorBreakpoint || breakpointValue + 1 === elementorBreakpoint;
					});

					breakpointToUpdate = elementorBreakpointsValues[ currentBreakpointIndex - 1 ];
				}

				swiperParameters.breakpoints[ breakpointToUpdate ] = swiperParameters.breakpoints[ breakpointValue ];
				swiperParameters.breakpoints[ breakpointValue ] = {
					slidesPerView: swiperParameters.slidesPerView,
					spaceBetween: swiperParameters.spaceBetween
				};

			} );

			return swiperParameters.breakpoints;

		},

	};

	$( window ).on( 'elementor/frontend/init', JetWooProductGallery.init );

	if ( window.JetPlugins ) {
		$( function () { JetPlugins.init() } );

		window.addEventListener( 'DOMContentLoaded', function() {
			JetWooProductGallery.initBlocks();
		} );
	}

	window.JetGallery = JetWooProductGallery;

}( jQuery ) );