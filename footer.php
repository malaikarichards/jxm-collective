	<footer class="bg-jxm-navy text-white mt-auto">
		<div class="container mx-auto px-5 py-12 lg:py-16">
			<div class="flex flex-col lg:flex-row lg:justify-between gap-10">
				<div>
					<div class="flex items-center gap-4 mb-8">
						<img
							src="<?php echo esc_url( jxm_asset( 'images/the-luxury-home-team-logo-white.png' ) ); ?>"
							
							alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
							class="h-16 w-auto border-r border-white pr-4"
						>
						<img 
						    src="<?php echo esc_url( jxm_asset( 'images/jxm-collective-logo-white.svg' ) ); ?>"
							alt="<?php esc_attr_e( 'The Luxury Home Team', 'jxm' ); ?>"
							class="h-16 w-auto"
						>
					</div>
					<p class="text-white text-sm max-w-md">
					South Florida's premier luxury real estate team, guiding discerning clients through exceptional properties.
					</p>
				</div>

				<div class="">
					<p class="text-white font-bold uppercase tracking-widest">
						Quick Links
</p>
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'footer',
							'container'      => false,
							'menu_class'     => '',
							'fallback_cb'    => false,
							'depth'          => 1,
						)
					);
					?>
				</div>
				<div class="">
					<p class="text-white font-bold uppercase tracking-widest">
						Connect
</p>
					<ul class="flex items-center gap-4 my-4">
						<li class="block"><a href="https://www.instagram.com/jxmcollective/" target="_blank" class="text-white bg-supporting rounded-full p-2"><i class="fa-brands fa-instagram"></i></a></li>
						<li class="block"><a href="https://www.facebook.com/jxmcollective/" target="_blank" class="text-white bg-supporting rounded-full p-2"><i class="fa-brands fa-facebook-f"></i></a></li>
					</ul>
					<p class="text-white font-bold uppercase tracking-widest mt-10 mb-4">
						Stay in the Loop
					</p>
					<!-- <div class="engage-hub-form-embed flex items-center gap-2" id="eh_form_4588890795802624"  data-id="4588890795802624"></div> -->
					<div id="eb-form-newsletter" class="flex items-center gap-2"></div>
					<script>
						(window.EhDynamicRef ||= []).push(() => {
							EhForms.create({
							"formId": "4588890795802624", // Required: The unique ID of your form
							"target": "#eb-form-newsletter", // Optional: Use a selector like ".class" or "#id"
							"onFormReady": function(el) {
								const iframe = document.getElementById('eh_form_ifrm_4588890795802624');
								const doc = (el && el.ownerDocument && el.ownerDocument !== document)
									? el.ownerDocument
									: (iframe && iframe.contentDocument);
								if (!doc) {
									return;
								}

								const svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path fill="white" d="M15.566 0.175119C15.8816 0.393869 16.0472 0.771994 15.9878 1.15012L13.9878 14.1501C13.941 14.4532 13.7566 14.7189 13.4878 14.8689C13.2191 15.0189 12.8972 15.0376 12.6128 14.9189L8.87533 13.3657L6.7347 15.6814C6.45658 15.9845 6.01908 16.0845 5.6347 15.9345C5.25033 15.7845 5.00033 15.4126 5.00033 15.0001V12.3876C5.00033 12.2626 5.0472 12.1439 5.13158 12.0532L10.3691 6.33762C10.5503 6.14074 10.5441 5.83762 10.3566 5.65012C10.1691 5.46262 9.86595 5.45012 9.66908 5.62824L3.31283 11.2751L0.55345 9.89387C0.2222 9.72824 0.00970049 9.39699 0.000325486 9.02824C-0.00904951 8.65949 0.184701 8.31574 0.503451 8.13137L14.5035 0.131369C14.8378 -0.0592555 15.2503 -0.0405055 15.566 0.175119Z"/></svg>';

								if (iframe) {
									iframe.style.setProperty('width', '100%', 'important');
									iframe.style.setProperty('min-width', '0', 'important');
								}

								if (!doc.getElementById('jxm-nl-btn-style')) {
									const style = doc.createElement('style');
									style.id = 'jxm-nl-btn-style';
									style.textContent = [
										'.body-wrapper,.form-wrapper,.form-container,.form-block{width:100%!important;max-width:none!important}',
										'.row.justify-content-center{justify-content:flex-start!important}',
										'html,body,.body-wrapper,.form-wrapper,.form-container,.form-block,.full-height{height:auto!important;min-height:0!important}',
										'.row.responsive{flex-wrap:nowrap!important;align-items:stretch!important}',
										'.row.responsive>.col,.row.responsive>.col.custom-width{flex:1 1 auto!important;max-width:none!important;width:auto!important}',
										'.row.responsive>.col.custom-width{flex:0 0 auto!important}',
										'.content-element.button{padding:0!important;height:100%}',
										'.btn-container{height:100%;display:flex!important}',
										'.eb-form-input{width:100%!important;height:42px!important;box-sizing:border-box;color:#fff!important;caret-color:#fff!important}',
										'.eb-form-input::placeholder{color:rgba(255,255,255,.6)!important;opacity:1!important}',
										'.eb-form-input::-webkit-input-placeholder{color:rgba(255,255,255,.6)!important}',
										'.eb-form-input::-moz-placeholder{color:rgba(255,255,255,.6)!important;opacity:1!important}',
										'.btn{background-image:url("data:image/svg+xml,' + encodeURIComponent(svg) + '")!important;background-repeat:no-repeat!important;background-position:center!important;background-size:16px 16px!important;color:transparent!important;font-size:0!important;width:43.5px!important;min-width:42px!important;height:43.5px!important;padding:0!important;box-sizing:border-box}',
									].join('');
									(doc.head || doc.documentElement).appendChild(style);
								}

								const btn = doc.querySelector('.btn');
								if (btn) {
									btn.setAttribute('aria-label', 'Subscribe');
								}

								const sizeIframe = () => {
									if (!iframe || !doc.documentElement) {
										return;
									}
									iframe.style.setProperty('width', '100%', 'important');
									const height = Math.max(
										doc.documentElement.scrollHeight,
										doc.body ? doc.body.scrollHeight : 0,
										64
									);
									iframe.style.setProperty('height', height + 'px', 'important');
								};

								sizeIframe();
								[50, 200, 600].forEach((ms) => window.setTimeout(sizeIframe, ms));
							}
						});
						});
					</script>
				</div>
			</div>

			<div class="mt-10 pt-6 border-t border-white text-sm text-center lg:text-left text-white flex flex-col lg:flex-row justify-between">
				<p class="mb-4 lg:mb-0">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'jxm' ); ?></p>
				<ul class="flex items-center gap-4 flex-col lg:flex-row">
					<li><a href="#" class="text-white">Privacy Policy</a></li>
					<li><a href="#" class="text-white">Terms of Use</a></li>
					<li><a href="#" class="text-white"><i class="fa-solid fa-house text-accent"></i> Equal Housing Opportunity</a></li>
				</ul>
			</div>
		</div>
	</footer>

	<?php wp_footer(); ?>

	<script type="text/javascript" >
	var EhAPI = EhAPI || {}; EhAPI.after_load = function(){
	EhAPI.set_account('av5ajeqd3jku45r32t5rp700qv', 'jxmcollective');
	EhAPI.execute('rules');};(function(d,s,f) {
	var sc=document.createElement(s);sc.type='text/javascript';
	sc.async=true;sc.src=f;var m=document.getElementsByTagName(s)[0];
	m.parentNode.insertBefore(sc,m);
	})(document, 'script', '//d2p078bqz5urf7.cloudfront.net/jsapi/ehform.js?v' + new Date().getHours());
	</script>
</body>
</html>
