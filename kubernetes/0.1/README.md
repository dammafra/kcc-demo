# Deploy of application v0.1
> We will issue our commands from the directory `kubernetes/0.1`.

The end goal is to have the application running. In order to achieve that, we will have to deploy both backend and frontend. Once that is done, we will need to setup the communication between them.
The only pod exposed to the outside world is the frontend. We will do this in two ways: via NodePort and with an Ingress.

## Deployment of backend and frontend
If we have a look at `deployments/backend-deployment.yaml` and `deployments/frontend-deployment.yaml` we will see that state the follow:
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
        image: fdammacco/kcc-backend:0.1
        # ports:
        #   - containerPort: 8080
```
```yaml
# deployments/backend-frontend.yaml
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
        image: fdammacco/kcc-frontend:0.1
        # ports:
        #   - containerPort: 80  
        env:
          - name: BACKEND_HOST
            value: backend-service
```
**Note:** the `spec.template.spec.containers.ports.containerPort` property is commented because, from the [API documentation](https://kubernetes.io/docs/reference/generated/kubernetes-api/v1.10/#container-v1-core):
> Any port which is listening on the default "0.0.0.0" address inside a container will be accessible from the network.

So we can start rolling out our application in the following way:
```bash
$ kubectl apply -f deployments/
deployment "backend" created
deployment "frontend" created
```
We can look at pods changes during the demo using the `watch` command:
```bash
$ watch kubectl get pods
Every 2,0s: kubectl get pods

NAME                        READY     STATUS    RESTARTS   AGE
backend-68cf74797b-2mp4z    1/1       Running   0          22s
backend-68cf74797b-8pplc    1/1       Running   0          22s
backend-68cf74797b-rbwlq    1/1       Running   0          22s
frontend-5f854b4979-t6vft   1/1       Running   0          22s
frontend-5f854b4979-wmbrl   1/1       Running   0          22s
frontend-5f854b4979-zlgp2   1/1       Running   0          22s
```
As we expected, we have three pods of backend and three pods of backend running on our cluster. Now we have to expose the frontend to the outside world and make it communicate with the backend.

## Expose the frontend
### NodePort
A Kubernetes [Service](https://kubernetes.io/docs/concepts/services-networking/service/) is an abstraction which defines a logical set of Pods and a policy by which to access them - sometimes called a micro-service. The set of Pods targeted by a Service is (usually) determined by a label selector.

Let's take a look to the `services/frontend-service.yaml` configuration file:
```yaml
# services/frontend-service.yaml
kind: Service
apiVersion: v1
metadata:
  name: frontend-service
spec:
  type: NodePort
  selector:
    app: frontend
  ports:
  - port: 80
```
As we ca se here, we are creating a service of `type: Nodeport` (in order to expose our frontend service outside the cluster), we are selecting all the pods with label `app=frontend` and we are exposing the port 80 on the IP address of the nodes.

**Note:** By specifying only the `spec.ports.port: 80` property we are telling kubernetes to map the port 80 of the selected pods to the port 80 exposed by the service. If we want to specify another pod port we can use the `spec.ports.targetPort` property. See the [documentation](https://kubernetes.io/docs/reference/generated/kubernetes-api/v1.10/#serviceport-v1-core) for more info.

Applying the configuration you'll get something like this:
```bash
$ kubectl apply -f services/frontend-service.yaml
service "frontend-service" created
```
```bash
$  kubectl get services
NAME               TYPE        CLUSTER-IP       EXTERNAL-IP   PORT(S)        AGE
frontend-service   NodePort    10.101.163.175   <none>        80:32354/TCP   15s
kubernetes         ClusterIP   10.96.0.1        <none>        443/TCP        28d
```
As you can see, the `frontend-service` is mapping the port 80 from the pod to a random external port, in this case 32354, on the nodes IP address. We have only one node in our cluster, minikube, and we can get its IP address with the command `minikube ip`.
**Note:** We can choose the external port by spcifying the `spec.ports.nodePort` property in the config file of the service.
```bash
$ minikube ip
192.168.146.52
```
So, by pointing with our browser to `http://192.168.146.52:32354` we can see our running frontend. 

It's interesting to notice that if we refresh the page, the frontend that is answering us could change, because there are three replicas of it running in our cluster.

### Ingress
Now let's expose the frontend using an [Ingress](https://kubernetes.io/docs/concepts/services-networking/ingress/). An Ingress is an API object that manages external access to the services in a cluster, typically HTTP.
```yaml
# ingresses/kcc-ingress.yaml
apiVersion: extensions/v1beta1
kind: Ingress
metadata:
  name: kcc-ingress
spec:
  rules:
    - host: kcc.local
      http:
        paths:
          - backend:
              serviceName: frontend-service
              servicePort: 80  
```
In the configuration file we can specify rules mapping the paths under a specified host to the related services. Incoming requests are first evaluated for a host match, then routed to the service associated with it.
We can configure our frontend as a `ClusterIP` service now, because is't the Ingress that will manage the external access. 

**Note:** we have to manually add an entry for our ingress in the `hosts` file of our machine, like:
```bash
# hosts
...
192.168.146.52  kcc.local
```
As always, let's apply our Ingress configuration 
```bash
$ kubectl apply -f ingresses/kcc-ingress.yaml
ingress "kcc-ingress" created
```
So, by pointing with our browser to `http://kcc.local` we can see our running frontend.

## Expose the backend
Now that we can access our frontend, we should see errors coused by the impossibility to establish a communication with the backend. If we take a look back to the `frontend-deployment.yaml` file we'll see these lines:
```yaml
# deployments/backend-frontend.yaml
... 
        env:
          - name: BACKEND_HOST
            value: backend-service
```
We are configuring our frontend with an environment variable called `BACKAND_HOST` that will contain the url of the backend to communicate with. In this case as url we can use just the name of the backend service because kubernetes will automatically do the resolution (more info [here](https://kubernetes.io/docs/concepts/services-networking/dns-pod-service/)).

So we can create our `backend-service` (in this example we are mapping the port 8080 from the Pods with the port 80 exposed by the service):
```yaml
# services/backend-service.yaml
kind: Service
apiVersion: v1
metadata:
  name: backend-service
spec:
  selector:
    app: backend
  ports:
  - port: 80
    targetPort: 8080
```
```bash
$ kubectl apply -f services/backend-service.yaml
service "backend-service" created
```
```bash
$ kubectl get services
NAME               TYPE        CLUSTER-IP       EXTERNAL-IP   PORT(S)        AGE
backend-service    ClusterIP   10.98.238.240    <none>        80/TCP         6s
frontend-service   NodePort    10.101.163.175   <none>        80:32354/TCP   5m
kubernetes         ClusterIP   10.96.0.1        <none>        443/TCP        29d
```
If we reload our frontend page everything should work. 

It's interesting to notice that if we refresh the page, the backend that is answering us could change, because there are three replicas of it running in our cluster.

## Next steps
Now we are ready for [update our application](../0.2/)!