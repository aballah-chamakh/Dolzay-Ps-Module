<div class="form-group">
    <label class="form-control-label required">{l s='City' mod='dolzay'}</label>
    <select name="city" id="city_select" class="form-control" required>
        <option value="">{l s='Select a city' mod='dolzay'}</option>
        {foreach from=$cities item=city_name}
            <option value="{$city_name|escape:'html':'UTF-8'}" {if $city_name == $city}selected{/if}>
                {$city_name|escape:'html':'UTF-8'}
            </option>
        {/foreach}
    </select>
</div>


<div class="form-group">
    <label class="form-control-label required">{l s='Delegation' mod='dolzay'}</label>
    <select name="delegation" id="delegation_select" class="form-control" required>
        <option value="">{l s='Select a delegation' mod='dolzay'}</option>
    </select>
</div>