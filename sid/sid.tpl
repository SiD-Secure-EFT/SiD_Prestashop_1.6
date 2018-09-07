{*
 * Copyright (c) 2018 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 * 
 * Released under the GNU General Public License
 *}
<p class="payment_module">
	<a id="sidButton" href="modules/sid/redirect.php" title="{l s='Pay with SID Instant EFT' mod='SID'}">
		<img src="{$module_template_dir}sid_logo.jpg" alt="{l s='Pay with SID Instant EFT' mod='SID'}" width="86" height="49"/>
		{l s='Pay with SID Instant EFT' mod='SID'}
	</a>
</p>

<script type="text/javascript">
	var sid_payment_data = {
		"SID_MERCHANT": "{$SID_MERCHANT}",
		"SID_CURRENCY": "{$SID_CURRENCY}",
		"SID_COUNTRY": "{$SID_COUNTRY}",
		"SID_REFERENCE": "{$SID_REFERENCE}",
		"SID_AMOUNT": "{$SID_AMOUNT}",		
		"SID_PRIVATE_KEY": "{$SID_PRIVATE_KEY}",
		"SID_CONSISTENT": "{$SID_CONSISTENT}"
	};

	$(document).ready(function() {
		$(document).on('click', '#sidButton', function(e) {
			e.preventDefault();
			SidEftJson(sid_payment_data);
		});
	});
</script>