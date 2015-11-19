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

<!-- Shipping Quote - validation.tpl -->
{capture name=path}{l s='Shipping' mod='shippingquote'}{/capture}
<h2>{l s='Shipping Information' mod='shippingquote'}</h2>
{assign var='current_step' value='shipping'}
{include file="$tpl_dir./order-steps.tpl"} 

<h3>{l s='Quote Requested' mod='shippingquote'}</h3>
	<div>
        <img src="{$this_path}shippingquote.jpg" alt="{l s='You may pay after receiving a shipping quote' mod='shippingquote'}" style="float:left; margin: 0px 40px 5px 0px;" />
	    <div style="font-weight:bold;">
	        {l s='We will respond with your quote as soon as possible.' mod='shippingquote'}
            {l s='For any questions or for further information, please contact our' mod='shippingquote'} <a style="text-decoration: none; color: #2E8BD8;" href="{$link->getPageLink('contact-form', true)}">{l s='Customer Support' mod='shippingquote'}</a>.
            <br /><br />
            {l s='Thank You!' mod='shippingquote'}<br />
        </div>
	</div>
<!-- /Shipping Quote - validation.tpl -->