<?php $this->layout = 'layouts/guest'; ?>
<?php $this->section('style'); ?>
<style>
    .payment-counter-section {
    background: #fff;
  }

  .counter-eyebrow {
    color: var(--brand-magenta);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 2px;
  }

  .counter-heading {
    color: var(--brand-purple);
    font-weight: 750;
    font-size: clamp(26px, 3vw, 38px);
  }

  .counter-card {
    padding: 30px 15px;
    border: 1px solid #eee5f4;
    border-radius: 18px;
    background: linear-gradient(
      145deg,
      #fff 0%,
      #faf5fd 100%
    );
    transition: transform .25s ease, box-shadow .25s ease;
  }

  .counter-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 30px rgba(73, 0, 135, .10);
  }

  .counter-icon {
    width: 54px;
    height: 54px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 18px;
    border-radius: 50%;
    background: linear-gradient(
      135deg,
      var(--brand-purple),
      var(--brand-magenta)
    );
    color: #fff;
    font-size: 24px;
    font-weight: 700;
  }

  .counter-number {
    color: var(--brand-purple);
    font-size: clamp(30px, 4vw, 42px);
    font-weight: 800;
    margin-bottom: 8px;
  }

  .counter-number::after {
    content: "+";
    color: var(--brand-magenta);
    font-size: .7em;
    margin-left: 3px;
  }

  .counter-label {
    color: #51445d;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 0;
  }
</style>
<?php $this->end(); ?>
<section class="hero" id="home"><div class="container"><div class="row align-items-center g-5">
    <div class="col-lg-6"><div class="eyebrow"><span class="eyebrow-dot"></span> Fast · Secure · Reliable</div><h1>Move money globally. <span>Exchange with confidence.</span></h1><p class="hero-copy">Turn your international payments and e-wallet balances into money you can use. Get clear exchange estimates and personal support when you need it.</p><div class="d-flex flex-wrap gap-3 mt-4"><a href="#calculator" class="btn btn-success btn-lg">Start an exchange <span aria-hidden="true">↗</span></a><a href="#services" class="btn btn-outline-dark btn-lg">Explore services</a></div><div class="trust-line"><span class="trust-icon">✓</span> Helpful support for your international payment needs</div></div>
    <div class="col-lg-6"><div class="position-relative" id="calculator"><div class="calc-card"><div class="d-flex justify-content-between align-items-start mb-4"><div><div class="calc-title">Exchange calculator</div><div class="calc-subtitle mt-1">Get an estimate before you trade</div></div><span class="badge rounded-pill text-bg-light border px-3 py-2">Live request</span></div>
      <div class="trade-tabs mb-4" role="tablist" aria-label="Trade direction"><button type="button" class="active" data-mode="buy" aria-pressed="true">I want to buy</button><button type="button" data-mode="sell" aria-pressed="false">I want to sell</button></div>
      <div class="mb-3"><label class="field-label" for="paymentMethod">Payment method</label><select class="form-select" id="paymentMethod"><option>PayPal (FNF)</option><option>PayPal (GNS)</option><option>Payoneer</option><option>ACH / Wire bank transfer</option><option>Visa / Mastercard gift card</option><option>Wise</option></select></div>
      <div class="field-label" id="amountLabel">You want to receive</div><div class="input-group mb-2"><input id="amount" class="form-control amount-field" type="number" min="0" step="any" value="500" aria-label="Amount"><span class="input-group-text currency-badge" id="sourceCurrency">USD</span></div>
      <div class="swap-divider"><span class="swap-circle" aria-hidden="true">↕</span></div>
      <div class="field-label" id="resultLabel">Estimated amount to pay</div><div class="input-group mb-3"><input id="resultAmount" class="form-control amount-field" type="text" value="Rate confirmation required" readonly aria-label="Estimated converted amount"><span class="input-group-text currency-badge" id="targetCurrency">NGN</span></div>
      <div class="rate-box d-flex align-items-center justify-content-between gap-3"><span>Final exchange rate</span><strong>Confirm before trading</strong></div>
      <a href="https://bensexchange.com/" class="btn btn-success btn-lg w-100 mt-3" id="requestTrade">Request a trade <span aria-hidden="true">→</span></a><p class="calc-note mb-0">This calculator is a UI preview. Confirm the live rate with Ben's Exchange before sending funds.</p>
    </div><div class="float-chip"><span>✓</span> Personal support when you need it</div></div></div>
  </div></div>
</section>
  <section class="payment-strip"><div class="container"><div class="row align-items-center gy-3"><div class="col-lg-3"><div class="payment-label">Payment methods we support</div></div><div class="col-lg-9"><div class="d-flex flex-wrap justify-content-lg-between align-items-center gap-4"><span class="payment-logo">PayPal</span><span class="payment-logo">Payoneer</span><span class="payment-logo">WISE</span><span class="payment-logo">BANK TRANSFER</span><span class="payment-logo">VISA <small>/</small> Mastercard</span></div></div></div></div>
</section>
<section class="payment-counter-section py-5">
  <div class="container">
    <div class="text-center mb-5">
      <span class="counter-eyebrow">PAYMENT SOLUTIONS</span>
      <h3 class="counter-heading mt-2">
        Payments Solution Is Here
      </h3>
      <p class="text-muted mb-0">
        Multiple payment options for your global transactions.
      </p>
    </div>

    <div class="row g-4 justify-content-center">

      <!-- PayPal -->
      <div class="col-6 col-md-3">
        <div class="counter-card text-center h-100">
          <div class="counter-icon">P</div>
          <h2 class="counter-number"
              data-count="1299">0</h2>
          <p class="counter-label">PayPal</p>
        </div>
      </div>

      <!-- Payoneer -->
      <div class="col-6 col-md-3">
        <div class="counter-card text-center h-100">
          <div class="counter-icon">P</div>
          <h2 class="counter-number"
              data-count="311">0</h2>
          <p class="counter-label">Payoneer</p>
        </div>
      </div>

      <!-- Bank Transfer -->
      <div class="col-6 col-md-3">
        <div class="counter-card text-center h-100">
          <div class="counter-icon">↔</div>
          <h2 class="counter-number"
              data-count="545">0</h2>
          <p class="counter-label">Bank Transfer (USD, CNY)</p>
        </div>
      </div>

      <!-- Stripe -->
      <div class="col-6 col-md-3">
        <div class="counter-card text-center h-100">
          <div class="counter-icon">S</div>
          <h2 class="counter-number"
              data-count="220">0</h2>
          <p class="counter-label">Stripe</p>
        </div>
      </div>

    </div>
  </div>
</section>
<section class="wallet-feature py-5" id="wallet-to-naira">
    <div class="container">
      <div class="row align-items-center g-4 g-lg-5">
        <div class="col-lg-5">
          <img class="wallet-feature-image img-fluid" src="<?= $_ENV['APP_URL'] ?>/wp-content/uploads/sites/51/2024/08/Financial-Solutions-Product-Collect-Image-1.webp" alt="Swap e-wallet funds to Nigerian Naira" loading="lazy">
        </div>
        <div class="col-lg-7">
          <div class="wallet-feature-icon mb-4" aria-hidden="true"><svg viewBox="0 0 48 48" width="42" height="42" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"><path d="M7 15h32l-8-8M41 15l-8 8M41 33H9l8 8M7 33l8-8"/></svg></div>
          <h2 class="wallet-feature-title">Swap Your E-wallet Funds To Naira</h2>
          <p class="wallet-feature-copy">Get amazing rates when you swap your e-wallet funds to naira. We accept a wide range of payment methods.</p>
          <a class="btn wallet-swap-btn mt-3" href="#calculator">Swap Now</a>
        </div>
      </div>
    </div>
  </section>
  <section class="wallet-feature py-5" id="wallet-to-naira">
    <div class="container">
      <div class="row align-items-center g-4 g-lg-5">
      <div class="col-lg-7">
          <div class="wallet-feature-icon mb-4" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/></svg></div>
          <h2 class="wallet-feature-title">Ben's Pay4Me</h2>
          <p class="wallet-feature-copy">We will help you make Payments such as domain purchases, Online shopping payments, Invoices from legit suppliers and other payments related to this.
          T&C apply</p>
          <a class="btn wallet-swap-btn mt-3" href="#calculator">Get Started</a>
        </div>
        <div class="col-lg-5">
          <img class="wallet-feature-image img-fluid" src="<?= $_ENV['APP_URL'] ?>/wp-content/uploads/sites/51/2024/08/Financial-Solutions-Product-Collect-Image-2.webp" alt="Swap e-wallet funds to Nigerian Naira" loading="lazy">
        </div>
        
      </div>
    </div>
  </section>
  <section class="section" id="services">
    <div class="container">
        <div class="text-center mx-auto mb-5" style="max-width:700px">
            <div class="section-kicker">What we can help with</div>
            <h2 class="section-heading">One partner for your global payment needs.</h2>
            <p class="section-copy mx-auto">Whether you're receiving funds, paying an international supplier, or moving e-wallet balances, get help finding the right next step.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4"><article class="service-card"><div class="service-icon" aria-hidden="true">↗</div><h3>International payments</h3><p>Get support with receiving or making international payments for your personal or business needs.</p><a class="text-link" href="/contact-us">Talk to our team <span>→</span></a></article></div></div>
  </div>
</section>
  <section class="section how-section" id="how-it-works"><div class="container"><div class="row align-items-end mb-5 gy-3"><div class="col-lg-7"><div class="section-kicker">Simple from start to finish</div><h2 class="section-heading mb-0">Your exchange, in three steps.</h2></div><div class="col-lg-5"><p class="section-copy mb-0">Know what to do next, with support available when you need clarification.</p></div></div><div class="row g-4"><div class="col-md-4"><div class="step-card"><div class="step-number">01</div><h3>Choose your service</h3><p>Select an available payment method and tell us what you need to exchange or pay.</p></div></div><div class="col-md-4"><div class="step-card"><div class="step-number">02</div><h3>Confirm the details</h3><p>Speak with our team to confirm the applicable rate, fees, payment instructions, and expected timing.</p></div></div><div class="col-md-4"><div class="step-card"><div class="step-number">03</div><h3>Complete your request</h3><p>Follow the confirmed instructions and contact support if you have questions along the way.</p></div></div></div></div></section>
  <section class="section"><div class="container"><div class="why-panel"><div class="row g-5 align-items-center"><div class="col-lg-6"><div class="section-kicker" style="color:#f0b4d2">Why Ben's Exchange</div><h2 class="section-heading text-white">A clearer way to handle international payments.</h2><p class="section-copy">We combine exchange services with practical guidance, helping you understand your options before you proceed.</p><a href="/about-us" class="btn btn-light mt-3">Learn about us <span aria-hidden="true">→</span></a></div><div class="col-lg-5 offset-lg-1"><div class="benefit"><div class="benefit-check">✓</div><div><strong>Clear rate confirmation</strong><p>Confirm the rate and applicable charges before authorising a transaction.</p></div></div><div class="benefit"><div class="benefit-check">✓</div><div><strong>Support when it matters</strong><p>Get assistance with payment options and transaction questions.</p></div></div><div class="benefit"><div class="benefit-check">✓</div><div><strong>Convenient service options</strong><p>Explore supported e-wallets, bank transfers, and Pay4Me requests in one place.</p></div></div></div></div></div></div></section>
  <section class="cta"><div class="container"><div class="cta-inner"><div><h2>Ready to make your next payment simpler?</h2><p>Tell us what you need and we'll help you understand the next step.</p></div><div class="d-flex flex-wrap gap-3"><a href="#calculator" class="btn btn-success btn-lg">Request an exchange ↗</a><a href="/contact-us" class="btn btn-outline-dark btn-lg">Contact us</a></div></div></div></section>

<?php $this->section('script'); ?>
<script>
  // Front-end preview only: no rate is fabricated and no trade is submitted.
  const amount = document.getElementById('amount');
  const resultAmount = document.getElementById('resultAmount');
  const sourceCurrency = document.getElementById('sourceCurrency');
  const targetCurrency = document.getElementById('targetCurrency');
  const amountLabel = document.getElementById('amountLabel');
  const resultLabel = document.getElementById('resultLabel');
  const modeButtons = document.querySelectorAll('[data-mode]');
  let mode = 'buy';
  function updateCalculator(){
    sourceCurrency.textContent = mode === 'buy' ? 'USD' : 'NGN';
    targetCurrency.textContent = mode === 'buy' ? 'NGN' : 'USD';
    amountLabel.textContent = mode === 'buy' ? 'You want to receive' : 'You want to sell';
    resultLabel.textContent = mode === 'buy' ? 'Estimated amount to pay' : 'Estimated amount to receive';
    resultAmount.value = 'Rate confirmation required';
  }
  modeButtons.forEach(button => button.addEventListener('click', () => {
    mode = button.dataset.mode;
    modeButtons.forEach(item => { const active = item === button; item.classList.toggle('active', active); item.setAttribute('aria-pressed', String(active)); });
    updateCalculator();
  }));
  amount.addEventListener('input', () => { resultAmount.value = 'Rate confirmation required'; });
  document.getElementById('year').textContent = new Date().getFullYear();
  updateCalculator();
</script>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    const counters = document.querySelectorAll(".counter-number");

    function animateCounter(counter) {
      if (counter.dataset.animated === "true") return;

      counter.dataset.animated = "true";

      const target = Number(counter.dataset.count);
      const duration = 2200;
      const startTime = performance.now();

      function updateCounter(now) {
        const progress = Math.min(
          (now - startTime) / duration,
          1
        );

        // Ease-out animation
        const eased = 1 - Math.pow(1 - progress, 3);
        const current = Math.floor(eased * target);

        counter.textContent = current.toLocaleString("en-US");

        if (progress < 1) {
          requestAnimationFrame(updateCounter);
        } else {
          counter.textContent = target.toLocaleString("en-US");
        }
      }

      requestAnimationFrame(updateCounter);
    }

    if ("IntersectionObserver" in window) {
      const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            animateCounter(entry.target);
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.4 });

      counters.forEach(counter => observer.observe(counter));
    } else {
      counters.forEach(animateCounter);
    }
  });
</script>
<script type="text/javascript">
// $(document).ready(function(){
	var type = 'buy';
	const trade_currency = 'USD';
    var value;
    var lx = null;
    var key, from, sendto, rate,rateData;
    var rateSet = <?=json_encode($rates)?>;
    var currencySet = <?=json_encode($currencies)?>;
    var method = $('input[name=paymth]').val().trim();
    console.log(rateSet)
    console.log(currencySet)
    var fvalue = $('#first').val().toLowerCase();
    console.log(fvalue+' '+method)
    from = 'ngn'; sendto = currencySet[fvalue].currency_code.substr(0,4);
    // rateData = [
    // currencySet[fvalue].min_buy_rate, 
    // currencySet[fvalue].max_buy_rate, 
    // currencySet[fvalue].min_sell_rate, 
    // currencySet[fvalue].max_sell_rate]
    var t = from.toLowerCase()+'_'+sendto.toLowerCase()+'_by_'+method.trim()+'_'+type;
    rateData = rateSet[t];
    console.log(rateData+' '+from+' '+t)
    calc(from, sendto, rateData);

    function calc(send, receive, rateData) {
        if (rateData === undefined) {
            $('#exchange').text('unAvailable').fadeIn(400);
            return;
        }
        rate = parseFloat(rateData);
        rateData = rateData.toString();
        if (rateData.startsWith("0")) {
            // rate = 1/parseFloat(rateData);
        }
        
        var x = "1 "+trade_currency.toUpperCase() +" = "+rate.toFixed(2)+" "+send.toUpperCase();
        console.log(x)
        lx = x;
        $('#exchange').text(x);
    }

    function whatsappme() {
    	var name,email;
    	var url = 'https://api.whatsapp.com/send?phone=2349064678063'
    	var from = $('#from').val();
    	var to = $('#to').val();
    	name = $('#name').val();
    	email = $('#email').val();
    	if ((from == '' && name == '') || type == '' || rateData == '') {
    		$('#myModal').css('display','none');
    		alert('Enter fields');
    		return;
    	}
    	console.log(currencySet);
    	console.log(sendto);
    	// https://api.whatsapp.com/send?phone=2349064678063&text=Send20%a20%quote
    	/*var xtr = '&text=I am '+name+'.\n My email is '+email+'.\n I want to '+type+' '+currencySet[sendto.toLowerCase()].name+' $'+to+'\n Rate is '+lx;*/
    	var xtr = '&text=I am '+name+'.\n My email is '+email+'.\n I want to '+type+' '+method.toUpperCase()+' $'+to+'\n Rate is '+lx;
    	let uri = url+xtr;
    	let encoded = encodeURI(uri);
    	$('#myModal').css('display','none');
    	window.open(encoded, '_blank').focus();
    	console.log('ok');
    }

    // radio func
    $('#s').addClass('send-buy-rev').removeClass('send-buy');
	$('#w').addClass('want-buy-rev').removeClass('want-buy');
	$('#s strong').text('You will pay');
	$('#w strong').text('I want to buy');
    	$('.fj-radio').click(function(event){
    		type = this.getAttribute("data-value");
    		if (type == 'buy') {
    			$('#s').addClass('send-buy-rev').removeClass('send-buy');
	$('#w').addClass('want-buy-rev').removeClass('want-buy');
    			$('#s strong').text('You will pay');
    			$('#w strong').text('I want to buy');
    			$('#sell').removeClass('fj-radio-active');
    		}else{
    			$('#s').addClass('send-buy-rev').removeClass('send-buy');
    			$('#w').addClass('want-buy-rev').removeClass('want-buy');
    			$('#s strong').text('You will get');
    			$('#w strong').text('I want to sell');
    			$('#buy').removeClass('fj-radio-active');
    		}
    		$('#'+type).addClass('fj-radio-active');
    		$('#from,#to').val('');
    		console.log('type: '+type);
    		rateData = rateSet[from+'_'+sendto+'_by_'+method+'_'+type];
        	
        	calc(from, sendto, rateData);
    	});
    	

    // calc frm sender
    document.getElementById("from")
      .addEventListener("input", function(event){
        var n = $('#from').val();
        console.log('working '+n)
        var res = parseFloat(n)/rate.toFixed(2);
        if (res >0) {
        	// if(res > 500){
        	// 	rate = type == 'buy' ? rateData[1] : rateData[3];
        	// }else{
        	// 	rate = type == 'buy' ? rateData[0] : rateData[2];
        	// }
        	console.log('type: '+type);
        	calc(from, sendto, rate);
            $('#to').val(res.toFixed(2));
        }else $('#to').val('');
    });
    // end

      // calc from receiver
    document.getElementById("to")
        .addEventListener("input", function(event){
            var n = $('#to').val();
            console.log('working '+n)
            if(n > 500){
        		rate = type == 'buy' ? (parseFloat(rateData) + 30): (parseFloat(rateData) + 30);
            	console.log('working rate '+rate)
        	}else{
        		rate = type == 'buy' ? rateData : rateData;
        	}
        	calc(from, sendto, rate);
        	// console.log(rate)
            var res = parseFloat(n)*rate;
            if (res >0) {
                $('#from').val(res.toFixed(2));
            }else $('#from').val('');
        });

	//DropDown input - select
    $('.t-dropdown').on('click', function() {
        $(this).parent().next().slideDown('fast');
        console.log('click')
    });

    $('.t-select-btn').on('click', function() {
       $('.t-dropdown-list').slideUp('fast');

        if(!$(this).prev().attr('disabled')){
        $(this).prev().trigger('click');
        }
    });

    

    $('.t-dropdown-list').width($('.t-dropdown-select').width());

    $('.t-dropdown-input').val('');

    $('li.t-dropdown-item').on('click', function() {
        value = this.getAttribute("data-value");
        image = this.getAttribute("data-image");
        key = this.getAttribute("data-key");
        
        if(key == 'receiver'){
            sendto = value.toLowerCase().substr(0,4);
            rateData = rateSet[from+'_'+sendto+'_by_'+method+'_'+type];
            /*rateData = currencySet[sendto];
            rateData = [
    rateData.min_buy_rate, 
    rateData.max_buy_rate, 
    rateData.min_sell_rate, 
    rateData.max_sell_rate]*/
            console.log(rateData);
            calc(from, sendto, rateData);
        }else if(key == 'paymth'){
        	method = value.toLowerCase();
        	rateData = rateSet[from+'_'+sendto+'_by_'+method+'_'+type];
        	console.log(value);
        	$('#to,#from').val('');
        	calc(from, sendto, rateData);
        }else{
            from = value.toLowerCase().substr(0,4);
            rateData = rateSet[from+'_'+sendto+'_by_'+method+'_'+type];
            /*rateData = currencySet[sendto];
            rateData = [
    rateData.min_buy_rate, 
    rateData.max_buy_rate, 
    rateData.min_sell_rate, 
    rateData.max_sell_rate]*/
            calc(from, sendto, rateData);
            if (rateData === undefined) {
                console.log('none')
            }
        }
        // console.log(key+" , "+value+"  ,frm= "+from+"  , to= "+sendto+",  rate= "+rateData);

       
        $('.'+key).html('<img src="'+image+'" alt="'+value+'"> '+value.toUpperCase().substr(0,19))
      // var text = $(this).html();
      // $(this).parent().prev().find('.t-dropdown-input').val(text);
      $('.t-dropdown-list').slideUp('fast');
    });
 

    $(document).on('click', function(event) {
      if ($(event.target).closest(".t-dropdown-input, .t-select-btn").length)
        return;
      $('.t-dropdown-list').slideUp('fast');
      event.stopPropagation();
    });
    // END //

// Get the modal
var modal = document.getElementById("myModal");

// Get the button that opens the modal
var btn = document.getElementById("myBtn");

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

// When the user clicks on the button, open the modal
btn.onclick = function() {
  modal.style.display = "block";
}

// When the user clicks on <span> (x), close the modal
span.onclick = function() {
  modal.style.display = "none";
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
  if (event.target == modal) {
    modal.style.display = "none";
  }
}
</script>
<?php $this->end(); ?>