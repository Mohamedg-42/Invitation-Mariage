/* ---------- Envelope open ---------- */
const envelopeScreen = document.getElementById('envelopeScreen');
const envelope = document.getElementById('envelope');
envelope.addEventListener('click', ()=>{
  if(envelope.classList.contains('open')) return;
  envelope.classList.add('open');
  setTimeout(()=>{
    envelopeScreen.classList.add('hidden');
    document.body.style.overflow = 'auto';
  }, 750);
});
document.body.style.overflow = 'hidden';
setTimeout(()=>{ if(!envelope.classList.contains('open')){ /* keep waiting for tap */ } }, 0);

/* ---------- Countdown ---------- */
const WEDDING_DATE = new Date("2026-11-21T12:00:00");
function updateCountdown(){
  const now = new Date();
  let diff = WEDDING_DATE - now;
  if(diff < 0) diff = 0;
  const days = Math.floor(diff / (1000*60*60*24));
  const hours = Math.floor((diff / (1000*60*60)) % 24);
  const mins = Math.floor((diff / (1000*60)) % 60);
  document.getElementById('cd-days').textContent = days;
  document.getElementById('cd-hours').textContent = hours;
  document.getElementById('cd-min').textContent = mins;
}
updateCountdown();
setInterval(updateCountdown, 30000);

/* ---------- Scroll reveal ---------- */
const revealEls = document.querySelectorAll('.reveal');
revealEls.forEach((el, i) => { el.style.transitionDelay = `${(i % 4) * 0.08}s`; });
const revealObserver = new IntersectionObserver((entries)=>{
  entries.forEach(entry=>{
    if(entry.isIntersecting){
      entry.target.classList.add('visible');
      revealObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.15 });
revealEls.forEach(el => revealObserver.observe(el));

/* ---------- RSVP ---------- */
const ORGANIZER_WHATSAPP = "2250703583299";
let rsvpChoice = null;

document.getElementById('giftsOpenBtn').addEventListener('click', ()=>{
  document.getElementById('giftsList').classList.toggle('open');
});

document.getElementById('rsvpOpenBtn').addEventListener('click', ()=>{
  document.getElementById('rsvpForm').classList.add('open');
});
document.getElementById('rsvpYes').addEventListener('click', (e)=>{
  rsvpChoice = 'oui';
  document.getElementById('rsvpYes').classList.add('active');
  document.getElementById('rsvpNo').classList.remove('active');
});
document.getElementById('rsvpNo').addEventListener('click', (e)=>{
  rsvpChoice = 'non';
  document.getElementById('rsvpNo').classList.add('active');
  document.getElementById('rsvpYes').classList.remove('active');
});

document.getElementById('rsvpSubmitBtn').addEventListener('click', ()=>{
  const name = document.getElementById('rsvpName').value.trim();
  const guests = Number(document.getElementById('rsvpGuests').value);
  const msg = document.getElementById('rsvpMsg');
  if(!name){
    msg.textContent = "Merci d'indiquer votre nom.";
    return;
  }
  if(!rsvpChoice){
    msg.textContent = "Merci de préciser si vous serez présent(e).";
    return;
  }
  if(!Number.isInteger(guests) || guests < 1){
    msg.textContent = "Merci d'indiquer un nombre de personnes valide.";
    return;
  }
  const waText = encodeURIComponent(
    rsvpChoice === 'oui'
      ? `Bonjour ! Je confirme ma présence au mariage (${name}, ${guests} personne(s)). 🎉`
      : `Bonjour, je ne pourrai malheureusement pas être présent(e) au mariage (${name}). Merci de votre compréhension.`
  );
  window.open(`https://wa.me/${ORGANIZER_WHATSAPP}?text=${waText}`, '_blank', 'noopener');
  msg.textContent = "WhatsApp va s'ouvrir pour envoyer votre réponse.";
});

try{
  const photoUrl = new URL('upload.html', window.location.href).href;
  new QRCode(document.getElementById("qrCode"), {
    text: photoUrl, width: 150, height: 150, colorDark: "#082E21", colorLight: "#ffffff"
  });
}catch(e){
  document.getElementById("qrCode").innerHTML = "";
}
