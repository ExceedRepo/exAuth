<?= $this->extend('exAuth\layout') ?>

<?= $this->section('title') ?>Magic Link Sent<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="container d-flex justify-content-center p-5">
    <div class="card col-12 col-md-5 shadow-sm">
        <div class="card-body text-center">
            <h5 class="card-title mb-4">Check Your Email</h5>
            <p>If an account matches that email, we have sent a magic login link. Please check your inbox.</p>
            <a href="<?= url_to('login') ?>" class="btn btn-outline-primary">Back to login</a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
