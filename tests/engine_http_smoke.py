from fastapi.testclient import TestClient
from engine.api.main import app

client = TestClient(app)
assert client.get('/health').status_code == 200
providers = client.get('/v1/providers').json()['data']
assert {'manual','wordpress','instagram','youtube','x'} <= {p['name'] for p in providers}
source={'id':'0199b3f8-b849-7d73-a90d-178ea09bb364','provider':'manual','name':'Desk'}
normalized=client.post('/v1/content/normalize',json={'source':source,'raw':{'external_id':'x1','title':'Pune Metro traffic update in Shivajinagar','body':'Heavy rain affects traffic near Shivajinagar.'}})
assert normalized.status_code == 200
content=normalized.json()['data']
analysis=client.post('/v1/content/analyze',json={'content':content,'taxonomy':[{'slug':'traffic'},{'slug':'metro'},{'slug':'weather'}],'locations':[{'id':'0199b3f8-b849-7d73-a90d-178ea09bb365','name':'Shivajinagar','slug':'shivajinagar'}]})
assert analysis.status_code == 200
assert analysis.json()['data']['locations'][0]['slug']=='shivajinagar'
print('FastAPI HTTP smoke: PASS')
