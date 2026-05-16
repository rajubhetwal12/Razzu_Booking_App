// Apply saved theme immediately (prevents FOUC)
(function(){var t=localStorage.getItem('lx_theme')||'dark';document.documentElement.setAttribute('data-theme',t)})();

document.addEventListener('DOMContentLoaded',function(){
  // Theme toggle
  var tb=document.getElementById('theme-btn');
  if(tb){
    var cur=document.documentElement.getAttribute('data-theme');
    tb.textContent=cur==='dark'?'☀️':'🌙';
    tb.onclick=function(){
      var n=document.documentElement.getAttribute('data-theme')==='dark'?'light':'dark';
      document.documentElement.setAttribute('data-theme',n);
      localStorage.setItem('lx_theme',n);
      tb.textContent=n==='dark'?'☀️':'🌙';
    };
  }

  // Hamburger
  var ham=document.getElementById('ham');
  var mob=document.getElementById('mob-nav');
  if(ham&&mob){ham.onclick=function(){mob.style.display=mob.style.display==='flex'?'none':'flex'}}

  // Auto-dismiss alerts
  document.querySelectorAll('.alert').forEach(function(el){
    setTimeout(function(){el.style.transition='opacity .5s';el.style.opacity='0';setTimeout(function(){el.remove()},500)},4500);
  });

  // Password eye toggles
  document.querySelectorAll('[data-eye]').forEach(function(btn){
    btn.onclick=function(){
      var t=document.getElementById(btn.getAttribute('data-eye'));
      if(t){t.type=t.type==='password'?'text':'password';btn.textContent=t.type==='password'?'👁️':'🙈';}
    };
  });

  // Init Three.js
  initHero();
});

function initHero(){
  var cv=document.getElementById('hero-canvas');
  if(!cv||typeof THREE==='undefined')return;

  var W=window.innerWidth,H=window.innerHeight;
  var renderer=new THREE.WebGLRenderer({canvas:cv,antialias:true,alpha:true});
  renderer.setSize(W,H);renderer.setPixelRatio(Math.min(devicePixelRatio,2));

  var scene=new THREE.Scene();
  var camera=new THREE.PerspectiveCamera(55,W/H,0.1,1000);
  camera.position.set(0,8,28);camera.lookAt(0,4,0);

  // Fog for depth
  scene.fog=new THREE.FogExp2(0x08081a,0.018);

  // Lights
  scene.add(new THREE.AmbientLight(0x8888bb,0.4));
  var g=new THREE.PointLight(0xd4a017,3,60);g.position.set(0,20,5);scene.add(g);
  var b=new THREE.PointLight(0x4455ff,2,80);b.position.set(-20,12,-10);scene.add(b);
  var w=new THREE.PointLight(0xffffff,1,40);w.position.set(16,8,12);scene.add(w);

  // Ground
  var ground=new THREE.Mesh(new THREE.PlaneGeometry(200,200),new THREE.MeshLambertMaterial({color:0x030308}));
  ground.rotation.x=-Math.PI/2;ground.position.y=-1;scene.add(ground);

  // Buildings config: [x, z, w, h, d]
  var bldgs=[[0,0,5,22,5],[-8,-2,3.5,14,3.5],[8,-2,3,17,3],[-14,-1,2.8,10,2.8],[14,-1,2.5,13,2.5],
             [-5,-10,2,8,2],[5,-10,2.2,9,2.2],[-11,-8,1.8,6,1.8],[11,-8,1.8,7.5,1.8],
             [-2,-16,1.5,5,1.5],[2,-16,1.6,5.5,1.6]];
  var wins=[];

  bldgs.forEach(function(b){
    var mat=new THREE.MeshLambertMaterial({color:new THREE.Color().setHSL(0.67,0.2,0.06)});
    var mesh=new THREE.Mesh(new THREE.BoxGeometry(b[2],b[3],b[4]),mat);
    mesh.position.set(b[0],b[3]/2-1,b[1]);scene.add(mesh);
    // Rooftop glow
    var gm=new THREE.Mesh(new THREE.BoxGeometry(b[2],0.2,b[4]),new THREE.MeshBasicMaterial({color:0xd4a017}));
    gm.position.set(b[0],b[3]-1,b[1]);scene.add(gm);
    // Windows
    var rows=Math.floor(b[3]/1.6),cols=Math.max(1,Math.floor(b[2]*1.2));
    for(var r=0;r<rows;r++){for(var c=0;c<cols;c++){
      var lit=Math.random()>0.35;
      var wm=new THREE.MeshBasicMaterial({color:lit?0xffd980:0x224488,transparent:true,opacity:Math.random()*0.5+0.25});
      var wn=new THREE.Mesh(new THREE.PlaneGeometry(0.28,0.38),wm);
      wn.position.set(b[0]-b[2]/2+(c+0.5)*(b[2]/cols)+0.05,r*1.55+0.4,b[1]+b[4]/2+0.06);
      scene.add(wn);wins.push(wm);
    }}
  });

  // Stars
  var pts=new Float32Array(300*3);for(var i=0;i<pts.length;i+=3){pts[i]=(Math.random()-.5)*90;pts[i+1]=Math.random()*45;pts[i+2]=(Math.random()-.5)*70;}
  var sg=new THREE.BufferGeometry();sg.setAttribute('position',new THREE.BufferAttribute(pts,3));
  scene.add(new THREE.Points(sg,new THREE.PointsMaterial({color:0xffd980,size:0.08,transparent:true,opacity:0.5})));

  var t=0;
  (function loop(){
    requestAnimationFrame(loop);t+=0.006;
    camera.position.x=Math.sin(t*0.22)*8;
    camera.position.z=28+Math.cos(t*0.16)*3;
    camera.lookAt(0,5,0);
    if(Math.random()>0.97){var ww=wins[Math.floor(Math.random()*wins.length)];if(ww)ww.opacity=Math.random()*0.5+0.2;}
    renderer.render(scene,camera);
  })();

  window.addEventListener('resize',function(){
    var nw=window.innerWidth,nh=window.innerHeight;
    camera.aspect=nw/nh;camera.updateProjectionMatrix();renderer.setSize(nw,nh);
  });
}
