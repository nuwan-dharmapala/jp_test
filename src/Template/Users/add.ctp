<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Log in'), ['action' => 'login']) ?></li>
    </ul>
</nav>
<div class="users form large-9 medium-8 columns content">
    <?= $this->Form->create($user) ?>
    <fieldset>
        <legend><?= __('Add User') ?></legend>
        <?php
            echo $this->Form->input('email');
            echo $this->Form->input('password');
        ?>
        <input type="hidden" id="country_idd" name="country_idd" />
        <?=  $this->Form->input('phone') ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>

<script>
    $(document).ready(function() {
        $("#phone").intlTelInput({
            nationalMode: false,
            separateDialCode: true,
            initialCountry: "ae",
            preferredCountries: [ "lk", "ae" ]
        });
        
        $("#phone").blur(function() {
            var countryData = $("#phone").intlTelInput("getSelectedCountryData");
            $("#country_idd").val(countryData.dialCode);
        });
    });
</script>
