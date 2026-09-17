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
const RSVP_KEY = "rsvp:data";
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

async function loadRsvps(){
  try{
    const res = await window.storage.get(RSVP_KEY, true);
    return res ? JSON.parse(res.value) : [];
  }catch(e){ return []; }
}
async function saveRsvps(list){
  await window.storage.set(RSVP_KEY, JSON.stringify(list), true);
}

document.getElementById('rsvpSubmitBtn').addEventListener('click', async ()=>{
  const name = document.getElementById('rsvpName').value.trim();
  const guests = document.getElementById('rsvpGuests').value || "1";
  const msg = document.getElementById('rsvpMsg');
  if(!name){
    msg.textContent = "Merci d'indiquer votre nom.";
    return;
  }
  if(!rsvpChoice){
    msg.textContent = "Merci de préciser si vous serez présent(e).";
    return;
  }
  msg.textContent = "Envoi en cours…";
  try{
    const list = await loadRsvps();
    list.push({
      id: `${Date.now()}-${Math.random().toString(36).slice(2,8)}`,
      name, guests, presence: rsvpChoice,
      date: new Date().toLocaleDateString('fr-FR')
    });
    await saveRsvps(list);
    msg.textContent = rsvpChoice === 'oui'
      ? "Merci ! Votre présence est confirmée avec joie."
      : "Merci de nous avoir prévenus, vous nous manquerez.";
    const waText = encodeURIComponent(
      rsvpChoice === 'oui'
        ? `Bonjour ! Je confirme ma présence au mariage (${name}, ${guests} personne(s)). 🎉`
        : `Bonjour, je ne pourrai malheureusement pas être présent(e) au mariage (${name}). Merci de votre compréhension.`
    );
    document.getElementById('rsvpWhatsappLink').href = `https://wa.me/${ORGANIZER_WHATSAPP}?text=${waText}`;
    document.getElementById('rsvpWhatsappWrap').style.display = 'block';
    document.getElementById('rsvpName').value = "";
  }catch(e){
    msg.textContent = "Échec de l'envoi, réessayez.";
  }
});

/* ---------- Photo gallery ---------- */
const GALLERY_PREFIX = "gallery:";

function compressImage(file, maxWidth=1100, quality=0.72){
  return new Promise((resolve, reject)=>{
    const reader = new FileReader();
    reader.onload = (e)=>{
      const img = new Image();
      img.onload = ()=>{
        let w = img.width, h = img.height;
        if(w > maxWidth){ h = Math.round(h * (maxWidth / w)); w = maxWidth; }
        const canvas = document.createElement('canvas');
        canvas.width = w; canvas.height = h;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, w, h);
        resolve(canvas.toDataURL('image/jpeg', quality));
      };
      img.onerror = reject;
      img.src = e.target.result;
    };
    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
}

async function loadGallery(){
  const galleryEl = document.getElementById('gallery');
  try{
    const listRes = await window.storage.list(GALLERY_PREFIX, true);
    const keys = (listRes && listRes.keys) ? listRes.keys : [];
    if(keys.length === 0){
      galleryEl.innerHTML = '<div class="empty">Aucune photo pour l\'instant — soyez les premiers à partager !</div>';
      return;
    }
    galleryEl.innerHTML = "";
    const sortedKeys = keys.slice().sort().reverse();
    for(const key of sortedKeys){
      try{
        const res = await window.storage.get(key, true);
        if(res && res.value){
          const img = document.createElement('img');
          img.src = res.value;
          img.loading = "lazy";
          img.alt = "Photo partagée par un invité";
          galleryEl.appendChild(img);
        }
      }catch(err){ }
    }
  }catch(e){
    galleryEl.innerHTML = '<div class="empty">Aucune photo pour l\'instant — soyez les premiers à partager !</div>';
  }
}
loadGallery();

try{
  const photoUrl = window.location.origin + window.location.pathname + "#photosSection";
  new QRCode(document.getElementById("qrCode"), {
    text: photoUrl, width: 150, height: 150, colorDark: "#082E21", colorLight: "#ffffff"
  });
}catch(e){
  document.getElementById("qrCode").innerHTML = "";
}

document.getElementById('photoInput').addEventListener('change', async (e)=>{
  const files = Array.from(e.target.files);
  if(files.length === 0) return;
  const statusEl = document.getElementById('uploadStatus');
  let done = 0;
  statusEl.textContent = `Envoi en cours… (0/${files.length})`;
  for(const file of files){
    try{
      const dataUrl = await compressImage(file);
      if(dataUrl.length * 0.75 > 4.5 * 1024 * 1024){ continue; }
      const key = `${GALLERY_PREFIX}${Date.now()}-${Math.random().toString(36).slice(2,8)}`;
      await window.storage.set(key, dataUrl, true);
      done++;
      statusEl.textContent = `Envoi en cours… (${done}/${files.length})`;
    }catch(err){ }
  }
  statusEl.textContent = done > 0 ? `${done} photo(s) ajoutée(s), merci !` : "Échec de l'envoi, réessayez.";
  document.getElementById('photoInput').value = "";
  loadGallery();
});

/* ---------- Admin ---------- */
const DEFAULT_ADMIN_CODE = "YVESIMMA2026";
const ADMIN_CODE_KEY = "admin:code";
const adminOverlay = document.getElementById('adminOverlay');
document.getElementById('adminOpenBtn').addEventListener('click', ()=>{ adminOverlay.classList.add('open'); });
document.getElementById('adminCloseBtn').addEventListener('click', ()=>{ adminOverlay.classList.remove('open'); });
adminOverlay.addEventListener('click', (e)=>{ if(e.target === adminOverlay) adminOverlay.classList.remove('open'); });

async function getAdminCode(){
  try{
    const res = await window.storage.get(ADMIN_CODE_KEY, true);
    return (res && res.value) ? res.value : DEFAULT_ADMIN_CODE;
  }catch(e){ return DEFAULT_ADMIN_CODE; }
}

function escapeHtml(str){
  const d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}

async function renderAdminRsvpList(){
  const list = await loadRsvps();
  const el = document.getElementById('adminTableList');
  if(list.length === 0){
    el.innerHTML = "<tr><td>Aucune réponse pour l'instant.</td></tr>";
    return;
  }
  el.innerHTML = "<tr><th>Nom</th><th>Présence</th><th>Pers.</th><th></th></tr>" +
    list.slice().reverse().map(r => `<tr><td>${escapeHtml(r.name)}</td><td>${r.presence === 'oui' ? '✔ Oui' : '✘ Non'}</td><td>${escapeHtml(String(r.guests))}</td><td><button class="admin-delete-btn" data-id="${r.id}">Supprimer</button></td></tr>`).join('');
  el.querySelectorAll('.admin-delete-btn').forEach(btn=>{
    btn.addEventListener('click', async ()=>{
      const id = btn.getAttribute('data-id');
      const current = await loadRsvps();
      const updated = current.filter(r => r.id !== id);
      await saveRsvps(updated);
      renderAdminRsvpList();
    });
  });
}

document.getElementById('adminUnlockBtn').addEventListener('click', async ()=>{
  const val = document.getElementById('adminPass').value;
  const msg = document.getElementById('adminGateMsg');
  const currentCode = await getAdminCode();
  if(val === currentCode){
    document.getElementById('adminGate').style.display = 'none';
    document.getElementById('adminPanel').style.display = 'block';
    renderAdminRsvpList();
  } else {
    msg.textContent = "Code incorrect.";
  }
});

document.getElementById('adminDownloadPhotosBtn').addEventListener('click', async ()=>{
  const msg = document.getElementById('adminPhotosMsg');
  msg.textContent = "Préparation du fichier…";
  try{
    const listRes = await window.storage.list(GALLERY_PREFIX, true);
    const keys = (listRes && listRes.keys) ? listRes.keys : [];
    if(keys.length === 0){
      msg.textContent = "Aucune photo à télécharger pour l'instant.";
      return;
    }
    const zip = new JSZip();
    let count = 0;
    for(const key of keys){
      try{
        const res = await window.storage.get(key, true);
        if(res && res.value){
          const base64Data = res.value.split(',')[1];
          const shortName = key.replace('gallery:', 'photo-') + '.jpg';
          zip.file(shortName, base64Data, { base64: true });
          count++;
        }
      }catch(err){ }
    }
    msg.textContent = `Compression de ${count} photo(s)…`;
    const blob = await zip.generateAsync({ type: "blob" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = "photos-yves-immaculee.zip";
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
    msg.textContent = `${count} photo(s) téléchargée(s) !`;
  }catch(e){
    msg.textContent = "Échec du téléchargement, réessaie.";
  }
});

document.getElementById('adminChangeCodeBtn').addEventListener('click', async ()=>{
  const newCode = document.getElementById('adminNewCode').value.trim();
  const msg = document.getElementById('adminChangeCodeMsg');
  if(newCode.length < 4){
    msg.textContent = "Choisis un code d'au moins 4 caractères.";
    return;
  }
  try{
    await window.storage.set(ADMIN_CODE_KEY, newCode, true);
    msg.textContent = "Code mis à jour avec succès !";
    document.getElementById('adminNewCode').value = "";
  }catch(e){
    msg.textContent = "Échec de l'enregistrement, réessaie.";
  }
});
