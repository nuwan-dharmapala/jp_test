<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('New User'), ['action' => 'add']) ?></li>
    </ul>
</nav>
<div class="users index large-9 medium-8 columns content">
    <h1>Login</h1>
    <?= $this->Form->create(NULL, [ 'id' => 'login-form']) ?>
    <?= $this->Form->input('email') ?>
    <?= $this->Form->input('password') ?>
    <?= $this->Form->button('Login') ?>
    <?= $this->Form->end() ?>
</div>

<div id="authy-modal" title="Please Authenticate">    
    <div class='auth-ot'>
        <div class='help-block'>
          <i class="fa fa-spinner fa-pulse"></i> Waiting for OneTouch Approval ...
        </div>
    </div>
    
    <div class='auth-token'>
        <div class='help-block'>
          <i class="fa fa-mobile"></i> Authy OneTouch not available
        </div>
        <p>Please enter your Token</p>
        <form id="authy-sms-form" class="form-horizontal" role="form" method="POST" action="/auth/twofactor">
          <input type="hidden" name="_token" value="{{ csrf_token() }}">
          <div class='form-group'>
            <label class="col-md-4 control-label" for="token">Authy Token</label>
            <div class='col-md-6'>
              <input type="text" name="token" id="authy-token" ng-model="token" value="" class="form-control" autocomplete="off" />
            </div>
          </div>
          <a value="Verify" class="btn btn-default" href="#" ng-click="cancel()">Cancel</a>
          <input type="submit" name="commit" value="Verify2" class="btn btn-success" ng-click="verifyToken(token)" />
        </form>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#login-form').submit(function(e) {
          e.preventDefault();
          formData = $(e.currentTarget).serialize();
          attemptOneTouchVerification(formData);
        });

        var attemptOneTouchVerification = function(form) {
          $.post( "/users/login_ajax", form, function(data) {
            var data = JSON.parse(data.replace(/1/g, ""));
            
            $('#authy-modal').dialog( "open" );
            
            if (data.success) {
                $('.auth-token').hide();
                $('.auth-ot').fadeIn();
                checkForOneTouch();
            } else {
                $('.auth-ot').hide();
                $('.auth-token').fadeIn();
            }
          });
        };

        var checkForOneTouch = function() {
          $.get( "/users/authy_status.json", function(data) {
              console.log(data.status);
                if (data.status == 'approved') {
                    window.location.href = "/";
                } else if (data.status == 'denied') {
                    showTokenForm();
                    triggerSMSToken();
                } else {
                    setTimeout(checkForOneTouch, 2000);
                }
          });
        };

        var showTokenForm = function() {
          $('.auth-ot').fadeOut(function() {
            $('.auth-token').fadeIn('slow');
          });
        };

        var triggerSMSToken = function() {
          $.get("/authy/send_token");
        };
    });
    
    $( "#authy-modal" ).dialog({
        autoOpen: false
    });
</script>
