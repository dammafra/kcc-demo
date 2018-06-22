# Deploy of application v0.2
> We will issue our commands from the directory `kubernetes/0.2`.

The end goal is to succesfully update the application. In order to achieve that, we will have to deploy newer versions of both backend and frontend. Once that is done, we will need to configure ConfigMaps and Secrets to manage configuration and sensible data, respectively.

## Update to v0.2
As already explained in [the first part of this demo](../0.0/), we can perform a rolling update of our application by applying new Deployment configurations to our cluster. Let's see them:
```yaml
# deployments/backend-deployment.yaml
apiVersion: extensions/v1beta1
kind: Deployment
metadata:
  name: backend
spec:
  replicas: 3
  template:
    metadata:
      labels:
        app: backend
    spec:
      containers:
      - name: backend
        image: fdammacco/kcc-backend:0.2
        # ports:
        #   - containerPort: 8080
```
```yaml
# deployments/backend-deployment.yaml
apiVersion: extensions/v1beta1
kind: Deployment
metadata:
  name: frontend
spec:
  replicas: 3
  template:
    metadata:
      labels:
        app: frontend
    spec:
      containers:
      - name: frontend
        image: fdammacco/kcc-frontend:0.2
        # ports:
        #   - containerPort: 8080
        env:
        - name: BACKEND_HOST
          value: backend-service
```
In both Deployments, Pods are now created from the version 0.2 of the `kcc-backend` and `kcc-frontend` docker images. Now we can apply this configurations and see what happens.
```bash
$ kubectl apply -f deployments/
deployment "backend" configured
deployment "frontend" configured
``` 
```bash
$ kubectl get pods                                                                                                                             
NAME                       READY     STATUS             RESTARTS   AGE
backend-6d66f68469-kcsvr   0/1       Error              3          3m
backend-6d66f68469-lfjxr   0/1       CrashLoopBackOff   3          3m
backend-6d66f68469-sfcrp   0/1       CrashLoopBackOff   3          3m
frontend-849cd7f8b-26znb   1/1       Running            0          3m
frontend-849cd7f8b-b8kqw   1/1       Running            0          3m
frontend-849cd7f8b-n2sh8   1/1       Running            0          3m
```
As we can see here, our rolling update is failing for backend. Let's fix this situation.

## Fixing the backend: ConfigMaps
We can begin fixing the backend using `kubectl logs <pod-name>` in order to read some usefult logs:
```bash
$  kubectl logs backend-6d66f68469-kcsvr
...

Error: Cannot find module '/etc/cfg/config.js'
    at Function.Module._resolveFilename (module.js:485:15)
    at Function.Module._load (module.js:437:25)
    at Module.require (module.js:513:17)
    at require (internal/module.js:11:18)
...
```
**Note:**  You can use `kubectl logs`, `kubectl exec`, or `kubectl describe` to help you debug if something goes wrong.

So our new backend needs a configuration file, ie `/etc/cfg/config.js`, in order to work properly.
```js
// configmaps/config.js
var config = {
    port: 8080
};

module.exports = config;
```
[ConfigMaps](https://kubernetes.io/docs/tasks/configure-pod-container/configure-pod-configmap/) allow you to decouple configuration artifacts from image content to keep containerized applications portable. And that's exactly what we need! Conceptually a ConfigMap is just a set of key-value pairs and can encapsulate only string data.

We have two ways of creating a CofingMap: the first is declarative and the second imperative.

### ConfigMap, the declarative way
They can be configured in a `.yaml` file.
```yaml
# configmaps/backend-configmap.yaml
apiVersion: v1
kind: ConfigMap
metadata:
  name: backend-configmap
data:
  config.js: |
    var config = {
      port: 8080
    };

    module.exports = config;
```
```bash
$ kubectl apply -f configmaps/
configmap "backend-configmap" created
```

### ConfigMap, the imperative way
Or they can be configured using `kubectl`:
```bash
$ kubectl create configmap backend-configmap --from-file=configmaps/config.js
configmap "backend-configmap" created
```

### Consuming ConfigMaps

There are two ways of consuming a ConfigMap: environment variables or file in a volume. As we need a file, we will use the secondo way. We have to add these lines to the `deployments/backend-deployment.yaml`:
```yaml
# deployments/backend-deployment.yaml
... 
spec:
  replicas: 3
  template:
    ...
    spec:
      containers:
      - ...
        volumeMounts:
          - name: config-volume
            mountPath: /etc/cfg
      volumes:
      - name: config-volume
        configMap:
          name: backend-configmap
          items:
          - key: config.js
            path: config.js      
```
So we are mounting a volume named `config-volume` under the path `/etc/cfg` in our Pod. This volume will contain a file named `config.js` filled with data from our configmap under the specified key `config.js`.

Updating our backend deployment we should fix the problem:
```bash
$ kubectl apply -f deployments/backend-deployment.yaml
deployment "backend" configured
```
```bash
$ kubectl get pods
NAME                       READY     STATUS    RESTARTS   AGE
backend-7dbb49954b-5zbnp   1/1       Running   0          57s
backend-7dbb49954b-gzv7q   1/1       Running   0          55s
backend-7dbb49954b-kbzzf   1/1       Running   0          57s
frontend-849cd7f8b-26znb   1/1       Running   0          40m
frontend-849cd7f8b-b8kqw   1/1       Running   0          40m
frontend-849cd7f8b-n2sh8   1/1       Running   0          40m
```  
Everything seems fine! Let's check our frontend...

## Fixing the frontend: Secrets
If we reload the page in our browser we can notice that the frontend is trying to autheticate against the backend, but it doesn't know the credentials.

Objects of type [Secret](https://kubernetes.io/docs/concepts/configuration/secret/) are intended to hold sensitive information, such as passwords, OAuth tokens, and ssh keys. Putting this information in a Secret is safer and more flexible than putting it verbatim in a pod definition or in a docker image. Conceptually a Secret is just a set of key-value pairs and can encapsulate only binary data.

You can create a secret by CLI or by `.yaml` file:
```yaml
# secrets/credentials-secret.yaml
apiVersion: v1
kind: Secret
metadata:
  name: credentials-secret
data:
  username: a2NjLXVzZXI= # echo 'kcc-user' | base64
  password: cGFzc3dvcmQ= # echo 'password' | base64
```
```bash
$ kubectl apply -f secrets/
secret "credentials-secret" created
```
You can use Secrets both as shared volumes and as environment variables, as ConfigMaps. This time we will use the environment variables approach, so we have to add these lines to the `deployments/frontend-deployment.yaml`:
```yaml
# deployments/frontend-deployment.yaml
...
        env:
        - name: BACKEND_HOST
          value: backend-service
        - name: USERNAME
          valueFrom:
            secretKeyRef: 
              name: credentials-secret
              key: username
        - name: PASSWORD
          valueFrom: 
            secretKeyRef:
              name: credentials-secret
              key: password
```
We are declaring two new environment variables, `USERNAME` and `PASSWORD`, that both take their value from the secret named `credentials-secret`, but from keys `username` and `password` respectively.

Updating our frontend deployment we should fix the problem, correctly authenticating against the backend.
```bash
$ kubectl apply -f deployments/frontend-deployment.yaml
deployment "frontend" configured
```

## Well done!
We have successfully deployed and managed a simple multi tier application on kubernetes. 