self.addEventListener('push',event=>{
  let data={};try{data=event.data?event.data.json():{};}catch(_){data={title:'Pune Mirror Now',body:event.data?.text()||'New Pune update'};}
  const title=data.title||'Pune Mirror Now';
  const options={body:data.body||'New Pune update',icon:data.icon||'./media/pmnow-logo.png',badge:data.badge||'./media/pmnow-logo.png',data:{url:data.url||'./notifications'}};
  event.waitUntil(self.registration.showNotification(title,options));
});
self.addEventListener('notificationclick',event=>{
  event.notification.close();
  const url=event.notification.data?.url||'./notifications';
  event.waitUntil(clients.matchAll({type:'window',includeUncontrolled:true}).then(wins=>{
    for(const win of wins){if('focus'in win){win.navigate(url);return win.focus();}}
    return clients.openWindow?clients.openWindow(url):undefined;
  }));
});
