{*
*  NOTICE OF LICENSE
*
*  This source file is subject to the Academic Free License (AFL 3.0)
*  that is bundled with this package in the file LICENSE.txt.
*  It is also available through the world-wide-web at this URL:
*  http://opensource.org/licenses/afl-3.0.php
*
*  This program is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of 
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.   
*}

{* Shipping Quote Request Button *}

<!-- Shipping Quote - request.tpl -->

{if $opc}

  <!-- One-page checkout -->
  {if !isPaymentStep}
    {literal}
    <style type="text/css">
      .payment_module {display: none;}      /* Supress Display Of Payment Modules */
    </style>
    <script>
      $(document).ready(function(){
        document.getElementsByTagName("h1")[3].style.display="none";
      });
    </script>
    {/literal}
  {/if}
  
  {literal}
  <script>
    /* Check Terms of Service */
    function ckTerms() {
      if (document.getElementById("cgv") && document.getElementById("cgv").checked === false) {
        location.href="#cgv";
        window.scrollBy(0,-100);      
        document.getElementById("warning").style.display="block";
        return false;      
      }
      return true;
    }
  </script>
  {/literal}
  <p class="shippingquote_module">
      <a id="quote_button" class="button_large" onclick="return ckTerms();"
         href="{$link->getModuleLink('shippingquote', 'validation', [], true)}">{l s='Request a Shipping Quote' mod='shippingquote'}</a>
  	<br /><br />
      <p><b>{l s='You may pay after receiving your shipping quote.' mod='shippingquote'}</b></p>
      <br />
      <div id="warning" style="display:none;"></div>
  </p>

{else}

  <!-- Standard (Five Steps) --> 
  {if !isPaymentStep}
    {literal}
    <style type="text/css">
      .standard-checkout {visibility: hidden;)     /* Proceed To Checkout button  */
    </style>
    {/literal}
  {/if}
  
  {literal}
  <script>
    /*Check Terms of Service */
    function ckTerms() {
      if (document.getElementById("cgv") && document.getElementById("cgv").checked === false) {
        location.href="#cgv";
        window.scrollBy(0,-100);      
        document.getElementById("warning").style.display="block";
        return false;      
      }
      return true;
    } 
  </script>
  {/literal}
  <p class="shippingquote_module">
      <a id="quote_button" class="button_large" onclick="return ckTerms();"
         href="{$link->getModuleLink('shippingquote', 'validation', [], true)}">{l s='Request a Shipping Quote' mod='shippingquote'}</a>
  	<br /><br />
      <p><b>{l s='You may pay after receiving your shipping quote.' mod='shippingquote'}</b></p>
      <br />
      <p id="warning" style="display:none;color:#990000;font-weight:bold;">{l s='Please accept the Terms of Service first.' mod='shippingquote'}</p>
  </p>

{/if}

<br style="clear:both;" />
<!-- /Shipping Quote - request.tpl --> 
