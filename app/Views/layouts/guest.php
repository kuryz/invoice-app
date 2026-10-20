<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'www.googletagmanager.com/gtm5445.html?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-M4HRCJB');</script>
    <!-- End Google Tag Manager -->
    <link rel="shortcut icon" href="<?=$_ENV['APP_URL']?>/img/logo.png">
    <title><?=$title ?? 'Ben\'s Exchange'?></title>
    <meta name='robots' content='max-image-preview:large' />
    
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="<?=$title ?? 'Ben\'s Exchange'?>">
    <!-- Google / Search Engine Tags -->
    <meta itemprop="name" content="Ben's Pay4Me">
    <meta itemprop="description" content="">
    <meta itemprop="image" content="<?=$_ENV['APP_URL']?>/wp-content/uploads/sites/51/2024/08/ben_logo.png">
    <!-- Facebook Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?=$title?? 'Ben\'s Exchange'?>">
    <meta property="og:description" content="Use our currency exchange services to receive and make international payments.">
    <meta property="og:image" content="<?=$_ENV['APP_URL']?>/wp-content/uploads/sites/51/2024/08/Financial-Solutions-Product-Collect-Image-2.webp"/>
    <meta property="og:image:type" content="<?=$_ENV['APP_URL']?>/wp-content/uploads/sites/51/2024/08/Financial-Solutions-Product-Collect-Image-2.webp" />
    <meta property="og:image:width" content="100" />
    <meta property="og:image:height" content="100" />
    <meta property="og:url" content="<?=$_ENV['APP_URL']?>">
    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    
    <link rel="alternate" type="application/rss+xml" title="Ben's Exchange &raquo; Feed" href="<?=$_ENV['APP_URL']?>" />
    <link rel="alternate" type="application/rss+xml" title="Ben's Exchange &raquo; Comments Feed" href="<?=$_ENV['APP_URL']?>" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Exchange international payments and e-wallet balances with Ben's Exchange. Request a trade or get help with international payments.">
  <title>Ben's Exchange — Global payments, made simpler</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?=$_ENV['APP_URL']?>/asset/css/styles.css">
  <?= $this->yield('style') ?>
</head>

<body>
<nav class="navbar navbar-expand-lg sticky-top border-bottom border-light-subtle">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="#home">
        <img style="height:82px; margin: auto; position: absolute;" src="<?=$_ENV['APP_URL']?>/wp-content/uploads/sites/51/2024/08/Logo-Financial-Solutions.webp" alt="B">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav mx-auto gap-lg-2"><li class="nav-item"><a class="nav-link" href="#services">Services</a></li><li class="nav-item"><a class="nav-link" href="#how-it-works">How it works</a></li><li class="nav-item"><a class="nav-link" href="/about-us">About</a></li><li class="nav-item"><a class="nav-link" href="/contact-us">Contact</a></li></ul>
      <div class="d-flex gap-2 mt-3 mt-lg-0"><a class="btn btn-outline-dark" href="/login">Log in</a><a class="btn btn-success" href="#calculator">Start exchange <span aria-hidden="true">↗</span></a></div>
    </div>
  </div>
</nav>
<main>
<?= $yield ?? '' ?>
</main>
<footer class="footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5">
                <a class="navbar-brand d-flex align-items-center mb-3" href="#home">
                <img style="height:60px; margin: auto; position: absolute;" src="<?=$_ENV['APP_URL']?>/wp-content/uploads/sites/51/2024/08/Logo-Financial-Solutions.webp" alt="B">
                </a>
            <p class="footer-copy">Payment and exchange support for e-wallet balances and international payment needs.</p></div><div class="col-6 col-lg-2"><div class="footer-title">Services</div><a class="footer-link" href="#services">E-wallet exchange</a><a class="footer-link" href="#services">Pay4Me</a><a class="footer-link" href="#services">International payments</a></div><div class="col-6 col-lg-2"><div class="footer-title">Company</div><a class="footer-link" href="/about-us">About us</a><a class="footer-link" href="/contact-us">Contact</a><a class="footer-link" href="/docs/ben_Terms_of_Use.pdf">Terms of use</a></div><div class="col-lg-3"><div class="footer-title">Need help?</div><p class="footer-copy">Contact our team to confirm rates, supported payment methods, and transaction requirements.</p><a class="text-link" href="/contact-us">Get in touch →</a></div></div><div class="footer-bottom d-flex flex-column flex-md-row justify-content-between gap-2"><span>© <span id="year"></span> Ben's Exchange. All rights reserved.</span>
            <span>Rates and transaction timing are subject to confirmation.</span>
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<script>
function openForm(key='') {
	var txt = null;
	if (key == 'swap') {
		txt = 'I want to swap';
	}else if(key == 'name'){
		txt = 'Can you help me pay for';
	}
	document.getElementById('myFormID').value = txt;
  document.getElementById("myForm").style.display = "block";
}

// $('#wp').click(function(){
// 	console.log('working');
// })
function closeForm() {
  document.getElementById("myForm").style.display = "none";
}
async function whatsappcont(){
	var name,email,purpose;
	name = $('input[name=fname]').val();
	email = $('input[name=femail]').val();
	purpose = $('input[name=fpurpose]').val();
	var url = 'https://api.whatsapp.com/send?phone=2349064678063';
	var baseUrl = "<?=$_ENV['APP_URL']?>";
	
	if (email == '' && name == '') {
		$('#myModal').css('display','none');
		alert('Enter fields');
		return;
	}
	
	$.post(baseUrl+"/api-subscribe", {
		name: name,
		email: email
	},
	function(data, status){
		console.log("Data: " + data + "\nStatus: " + status);
	});
	// https://api.whatsapp.com/send?phone=2349064678063&text=Send20%a20%quote
	var xtr = '&text=I am '+name+'.\n My email is '+email+'.\n'+purpose;
	let uri = url+xtr;
	let encoded = encodeURI(uri);
	$('#myModal').css('display','none');
	closeForm();
	window.open(encoded, '_blank').focus();
}
</script>
<?= $this->yield('script'); ?>
</body>
</html>
