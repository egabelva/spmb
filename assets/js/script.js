/* Logika sisi klien SPMB SMA Gunung Madu */
function toggleTheme(){
  var d=document.documentElement.classList.toggle('dark');
  try{localStorage.setItem('theme',d?'dark':'light');}catch(e){}
}
function openModal(id){var m=document.getElementById(id);if(m)m.classList.add('open');}
function closeModal(el){var m=el.closest?el.closest('.modal'):el;if(m)m.classList.remove('open');}

document.addEventListener('click',function(ev){
  var t=ev.target;
  var op=t.closest('[data-open]');
  if(op){ev.preventDefault();openModal(op.getAttribute('data-open'));return;}
  if(t.closest('[data-close]')){ev.preventDefault();closeModal(t.closest('.modal'));return;}
  if(t.classList&&t.classList.contains('modal')){t.classList.remove('open');return;}
  var cf=t.closest('a[data-confirm]');
  if(cf){
    ev.preventDefault();
    document.getElementById('confirm-text').textContent=cf.getAttribute('data-confirm');
    document.getElementById('confirm-ok').setAttribute('href',cf.getAttribute('href'));
    openModal('modal-confirm');return;
  }
  var nb=t.closest('#navBtn');
  if(nb){document.getElementById('navMenu').classList.toggle('hidden');return;}
  if(t.closest('#navMenu a')){document.getElementById('navMenu').classList.add('hidden');}
});
document.addEventListener('keydown',function(e){
  if(e.key==='Escape'){document.querySelectorAll('.modal.open').forEach(function(m){m.classList.remove('open');});}
});

/* Hitung mundur pendaftaran */
(function(){
  var el=document.getElementById('countdown');
  if(!el)return;
  var dl=el.getAttribute('data-deadline');
  if(!dl){el.innerHTML='<span class="text-sm opacity-90">Tanggal penutupan belum ditetapkan</span>';return;}
  var target=new Date(dl.replace(' ','T')).getTime();
  function pad(n){return String(n).padStart(2,'0');}
  function tick(){
    var s=Math.floor((target-Date.now())/1000);
    if(s<=0){el.innerHTML='<span class="font-bold">Pendaftaran telah ditutup</span>';clearInterval(iv);return;}
    document.getElementById('cd-d').textContent=pad(Math.floor(s/86400));
    document.getElementById('cd-h').textContent=pad(Math.floor(s%86400/3600));
    document.getElementById('cd-m').textContent=pad(Math.floor(s%3600/60));
    document.getElementById('cd-s').textContent=pad(s%60);
  }
  var iv=setInterval(tick,1000);tick();
})();

/* Halaman login: tab & peran */
function pilihTab(t){
  document.querySelectorAll('[data-tab]').forEach(function(p){p.classList.toggle('hidden',p.getAttribute('data-tab')!==t);});
  document.querySelectorAll('[data-tabbtn]').forEach(function(b){
    var a=b.getAttribute('data-tabbtn')===t;
    b.classList.toggle('bg-orange-500',a);b.classList.toggle('text-white',a);
  });
}
function pilihPeran(sel){
  var pend=sel.value==='Pendaftar';
  document.querySelectorAll('[data-peran="pendaftar"]').forEach(function(x){x.classList.toggle('hidden',!pend);});
  document.querySelectorAll('[data-peran="kode"]').forEach(function(x){x.classList.toggle('hidden',pend);});
  var i=document.getElementById('ident'),k=document.getElementById('kode');
  if(i)i.required=pend; if(k)k.required=!pend;
  var pw=document.getElementById('pw'); if(pw)pw.required=pend;
}

/* Progres formulir pendaftaran */
function hitungProgres(){
  var form=document.getElementById('form-daftar');
  if(!form)return;
  var req=form.querySelectorAll('[data-req]'),isi=0;
  req.forEach(function(f){if(f.type==='file'){if(f.files.length||f.getAttribute('data-ada')==='1')isi++;}else if(f.value.trim()!=='')isi++;});
  var p=Math.round(isi/req.length*100);
  var bar=document.getElementById('bar-progres'),txt=document.getElementById('txt-progres');
  if(bar)bar.style.width=p+'%'; if(txt)txt.textContent=p+'% ('+isi+' dari '+req.length+' bagian terisi)';
}
document.addEventListener('input',function(e){if(e.target.closest&&e.target.closest('#form-daftar'))hitungProgres();});
document.addEventListener('change',function(e){if(e.target.closest&&e.target.closest('#form-daftar'))hitungProgres();});
document.addEventListener('DOMContentLoaded',hitungProgres);

/* Operator: modal edit konten */
function bukaEdit(btn){
  var d=JSON.parse(btn.getAttribute('data-item'));
  var f=document.getElementById('form-konten'),E=f.elements;
  E['id'].value=d.id;E['bagian'].value=d.bagian;E['judul'].value=d.judul||'';E['isi'].value=d.isi||'';
  E['tautan'].value=d.tautan||'';E['urutan'].value=d.urutan||0;E['gambar'].value='';
  E['hapus_gambar'].checked=false;
  document.getElementById('konten-judul').textContent=(d.id>0?'Edit ':'Tambah ')+d.label;
  document.getElementById('konten-hint').textContent=d.hint;
  document.getElementById('konten-hapus').classList.toggle('hidden',!(d.id>0));
  document.getElementById('konten-hapusgbr').classList.toggle('hidden',!d.ada_gambar);
  document.getElementById('hapus-id').value=d.id;
  document.getElementById('hapus-bagian').value=d.bagian;
  openModal('modal-konten');
}

/* Admin: filter + pencarian tabel, modal detail */
function filterTabel(){
  var q=(document.getElementById('cari')||{value:''}).value.toLowerCase();
  var s=(document.getElementById('filter-status')||{value:''}).value;
  var n=0;
  document.querySelectorAll('#tabel-pendaftar tbody tr[data-status]').forEach(function(tr){
    var ok=(!s||tr.getAttribute('data-status')===s)&&(!q||tr.getAttribute('data-cari').indexOf(q)>-1);
    tr.style.display=ok?'':'none'; if(ok)n++;
  });
  var c=document.getElementById('jml-tampil');if(c)c.textContent=n;
  var ex=document.getElementById('link-export');
  if(ex)ex.href='admin_export.php'+(s?('?status='+encodeURIComponent(s)):'');
}
function bukaDetail(tr){
  var d=JSON.parse(tr.getAttribute('data-detail'));
  var rows=[['No. Pendaftaran',d.no_pendaftaran],['NIK / NISN',d.nik_nisn],['Nama lengkap',d.nama_lengkap],['Jenis kelamin',d.jenis_kelamin],
    ['Tempat, tanggal lahir',d.tempat_lahir+', '+d.tgl_lahir],['Alamat',d.alamat],['Asal sekolah',d.asal_sekolah],['Jalur',d.jalur],
    ['Orang tua/wali',d.tipe_batih+' - '+d.nama_batih],['Pekerjaan',d.pekerjaan_batih],['Kontak',d.kontak_batih],
    ['Berkas',d.nama_berkas||'(tidak ada)'],['Sumber',d.sumber],['Waktu daftar',d.waktu],['Status',d.status]];
  var h='';rows.forEach(function(r){h+='<div class="py-2 border-b border-gray-100 dark:border-gray-700 sm:flex"><dt class="sm:w-48 text-xs font-semibold text-gray-500">'+r[0]+'</dt><dd class="font-medium break-words">'+(r[1]===null||r[1]===undefined?'-':String(r[1]).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}))+'</dd></div>';});
  document.getElementById('detail-isi').innerHTML=h;
  var t=window.CSRF||'';
  document.getElementById('d-berkas').classList.toggle('hidden',!d.nama_berkas);
  document.getElementById('d-berkas').href='lihat_berkas.php?id='+d.id;
  document.getElementById('d-bukti').href='bukti_pendaftaran.php?id='+d.id;
  document.getElementById('d-terima').href='admin_dashboard.php?aksi=terima&id='+d.id+'&t='+t;
  document.getElementById('d-pending').href='admin_dashboard.php?aksi=pending&id='+d.id+'&t='+t;
  var tl=document.getElementById('d-tolak');
  tl.href='admin_dashboard.php?aksi=tolak&id='+d.id+'&t='+t;
  tl.setAttribute('data-confirm','Yakin menolak pendaftaran '+d.nama_lengkap+'?');
  openModal('modal-detail');
}
document.addEventListener('DOMContentLoaded',filterTabel);

/* Buka jendela kartu dari hash (#m-profil dst.), dipakai operator setelah simpan/hapus konten */
document.addEventListener('DOMContentLoaded',function(){
  var h=location.hash;
  if(h.indexOf('#m-')===0){openModal('info-'+h.slice(3).replace(/[^a-z0-9_-]/gi,''));}
});
