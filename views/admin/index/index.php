<form class="form-horizontal" method="POST" action="<?= $this->getUrl(['action' => 'save']) ?>">
<legend><?=$this->getTrans('settings') ?></legend>
    <div class="alert alert-info">
        <?=$this->getTrans('secret_info') ?>
    </div>
    <?=$this->getTokenField() ?>
    <div class="form-group">
        <label for="consumerKeyInput" class="col-lg-2 control-label">
            <?=$this->getTrans('consumer_key') ?>:
        </label>
        <div class="col-lg-10">
            <input type="text"
                   class="form-control"
                   id="consumerKeyInput"
                   name="consumerKey"
                   value="<?=$this->escape($this->get('twitterauth')['consumerKey']) ?>" />
        </div>
    </div>
    <div class="form-group">
        <label for="consumerSecretInput" class="col-lg-2 control-label">
            <?=$this->getTrans('consumer_secret') ?>:
        </label>
        <div class="col-lg-10">
            <input type="text"
                   class="form-control"
                   id="consumerSecretInput"
                   name="consumerSecret"
                   value="" />
            <?php if (!empty($this->escape($this->get('twitterauth')['consumerSecret']))): ?>
                <span class="help-block">
                    <span class="text-success"><?=$this->getTrans('consumer_secret_set') ?></span>
                </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="form-group">
        <label for="accessTokenInput" class="col-lg-2 control-label">
            <?=$this->getTrans('access_token') ?>:
        </label>
        <div class="col-lg-10">
            <input type="text"
                   class="form-control"
                   id="accessTokenInput"
                   name="accessToken"
                   value="<?=$this->escape($this->get('twitterauth')['accessToken']) ?>" />
        </div>
    </div>
    <div class="form-group">
        <label for="accessTokenSecretInput" class="col-lg-2 control-label">
            <?=$this->getTrans('access_token_secret') ?>:
        </label>
        <div class="col-lg-10">
            <input type="text"
                   class="form-control"
                   id="accessTokenSecretInput"
                   name="accessTokenSecret"
                   value="" />
            <?php if (!empty($this->escape($this->get('twitterauth')['accessTokenSecret']))): ?>
                <span class="help-block">
                    <span class="text-success"><?=$this->getTrans('access_token_secret_set') ?></span>
                </span>
            <?php endif; ?>
        </div>
    </div>
    <?=$this->getSaveBar() ?>
</form>

<div class="panel panel-info">
        <div class="panel-heading">
            <?=$this->getTrans('get_your_keys') ?>
        </div>
        <div class="panel-body">
            <?=$this->getTrans('get_your_keys_text') ?>
        </div>
    </div>
