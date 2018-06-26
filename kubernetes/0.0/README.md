# From zero...
> We will issue our commands from the directory `kubernetes/0.0`.

## Pods
[Pods](https://kubernetes.io/docs/concepts/workloads/pods/pod/) are the smallest deployable units of computing that can be created and managed in Kubernetes. Here is a simple example of Pod:
```yaml
# pods/prod-pod.yaml
apiVersion: v1
kind: Pod
metadata:
  name: prod
  labels:
    env: prod
spec:
  containers:
  - name: nginx
    image: nginx:1.13-alpine
```
First of all, make sure that minikube is up and running:
``` bash
$ minikube status
minikube: Running
cluster: Running
kubectl: Correctly Configured: pointing to minikube-vm at 192.168.146.60
```
Then let's see the current state of our cluster:
```bash
$ kubectl get pods

No resources found
```
So, we have a clean cluster.

In order to create our pods we will use the `kubectl apply` command, that applies a configuration to a resource by filename or stdin. The resource will be created if it doesn’t exist yet.
```bash
$ kubectl apply -f pods/
pod "prod" created
pod "test" created
```
```bash
$ kubectl get pods --show-labels
NAME      READY     STATUS    RESTARTS   AGE       LABELS
prod      1/1       Running   0          37s       env=prod
test      1/1       Running   0          37s       env=test
```
As defined in our `prod-pod.yaml` and `test-pod.yaml` we now have 2 running pods in our cluster, all running nginx version 1.13-alpine and with the labels set as expected.

## ReplicationControllers
A [ReplicationController](https://kubernetes.io/docs/concepts/workloads/controllers/replicationcontroller/) ensures that a specified number of pod replicas are running at any one time. In other words, a ReplicationController makes sure that a pod or a homogeneous set of pods is always up and available. Here is a simple example of ReplicationController:
```yaml
# replicationcontrollers/test-replicationcontroller.yaml
apiVersion: v1
kind: ReplicationController
metadata:
  name: test
spec:
  replicas: 2
  selector:
    env: test
  template:
    metadata:
      name: test
      labels:
        env: test
    spec:
      containers:
      - name: nginx
        image: nginx:1.13-alpine
```
If we apply this configuration, we will notice that only another pod with label `env=test` will be created.
```bash
$ kubectl apply -f replicationcontrollers/
replicationcontroller "test" created
```
```bash
$ kubectl get pods --show-labels
NAME         READY     STATUS    RESTARTS   AGE       LABELS
prod         1/1       Running   0          12m       env=prod
test         1/1       Running   0          12m       env=test
test-lkvqj   1/1       Running   0          27s       env=test
```
With a ReplicationController we can perform an imperative rolling update over the pods like this:
```bash
$ kubectl rolling-update test --image=nginx:1.14-alpine --update-period=5s
Created test-9d289bbdae6a880f0d805e2fc72060b3
Scaling up test-9d289bbdae6a880f0d805e2fc72060b3 from 0 to 2, scaling down test from 2 to 0 (keep 2 pods available, don't exceed 3 pods)
Scaling test-9d289bbdae6a880f0d805e2fc72060b3 up to 1
Scaling test down to 1
Scaling test-9d289bbdae6a880f0d805e2fc72060b3 up to 2
Scaling test down to 0
Update succeeded. Deleting old controller: test
Renaming test-9d289bbdae6a880f0d805e2fc72060b3 to test
replicationcontroller "test" rolling updated
```
We can look at pods changes during the update using the `watch` command:
```bash
$ watch kubectl get pods
Every 2,0s: kubectl get pods

NAME                                          READY     STATUS              RESTARTS   AGE
prod                                          1/1       Running             0          2m
test                                          1/1       Running             0          43s
test-9d289bbdae6a880f0d805e2fc72060b3-9k5s2   1/1       Running             0          8s
test-9d289bbdae6a880f0d805e2fc72060b3-s24gb   0/1       ContainerCreating   0          3s
test-lkvqj                                    0/1       Terminating         0          37s
```
What we see here is interesting. As soon as we rolled out our update, kubernetes has started to terminate running pods with the old version and has created new ones aligned with the new state we have specified. It has done that, by creating a new ReplicationController. So the old ReplicationController gradually diminished the number of running pods while the new increased it to finally reach desired state.

Now, if we delete the ReplicationController we will see that the pods associated to it will be deleted too.
```bash
$ kubectl delete replicationcontroller test
replicationcontroller "test" deleted
```
```bash
$ kubectl get pods --show-labels
NAME      READY     STATUS    RESTARTS   AGE       LABELS
prod      1/1       Running   0          2h        env=prod
```

## ReplicaSets
A [ReplicaSet](https://kubernetes.io/docs/concepts/workloads/controllers/replicaset/) is the "next-generation" ReplicationController. The only difference between a ReplicaSet and a ReplicationController right now is the set-based selector support (ReplicationController only supports equality-based selector). Here is a simple example of ReplicaSet:
```yaml
# replicasets/testprod-replicaset.yaml
apiVersion: apps/v1
kind: ReplicaSet
metadata:
  name: testprod
spec:
  replicas: 2
  selector:
    matchExpressions:
      - {key: env, operator: In, values: [test, prod]}
  template:
    metadata:
      name: test
      labels:
        env: test
    spec:
      containers:
      - name: nginx
        image: nginx:1.13-alpine
```
Let's bring us back to the initial state of the cluster, with two pods test and prod running.
```bash
$ kubectl apply -f pods/
pod "prod" unchanged
pod "test" created
```
```bash
$  kubectl get pods --show-labels
NAME      READY     STATUS    RESTARTS   AGE       LABELS
prod      1/1       Running   0          2h        env=prod
test      1/1       Running   0          6s        env=test
```
If we apply the ReplicaSet configuration above, we will notice that no pod will be added, because both test and prod pods match the `selector.matchExpressions` condition.
```bash
$ kubectl apply -f replicasets/
replicaset "testprod" created
```
Now, if we delete the ReplicationController we will see that the pods associated to it will be deleted too.
```bash
$ kubectl delete replicaset testprod
replicaset "testprod" deleted
```
```bash
$ kubectl get pods --show-labels
No resources found.
```
Notice that most `kubectl` commands that support ReplicationControllers also support ReplicaSets. One exception is the `rolling-update` command. If you want the rolling update you have to use using Deployments, that will be covered in next paragraph. Also, the rolling-update command is imperative whereas Deployments are declarative, so it's recommended using Deployments.

## Deployments
So, a [Deployments](https://kubernetes.io/docs/concepts/workloads/controllers/deployment/) controller provides declarative updates for Pods and ReplicaSets. You describe a desired state in a Deployment object, and the Deployment controller changes the actual state to the desired state at a controlled rate. Typically, you don't have to use Pods or ReplicationControllers/Replicasets directly, but you can rely only on Deployments. Here is a simple example of Deployment:
```yaml
# deployments/nginx-deployment.yaml
apiVersion: extensions/v1beta1
kind: Deployment
metadata:
  name: nginx
spec:
  replicas: 2
  template:
    metadata:
      labels:
        app: nginx
    spec:
      containers:
      - name: nginx
        image: nginx:1.13-alpine      
```
If we apply this configuration, we will see that will be created two pods with label `app=nginx` and a ReplicaSet to manage their replicas.
```bash
$ kubectl apply -f deployments/nginx-deployment.yaml
deployment "nginx" created
```
```bash
$ kubectl get pods --show-labels
NAME                     READY     STATUS    RESTARTS   AGE       LABELS
nginx-5f88d57b88-j55dv   1/1       Running   0          1h        app=nginx
nginx-5f88d57b88-w7css   1/1       Running   0          1h        app=nginx
```
```bash
$ kubectl get replicasets
NAME               DESIRED   CURRENT   READY     AGE
nginx-5f88d57b88   2         2         2         1h 
```
We can perform a declarative rolling update for the pods in the deployment by simpling editing the `deployments/nginx-deployment.yaml` file and re-apply it to our cluster, for example we can update the nginx version running on each pod like this:
```yaml
# deployments/nginx-deployment.yaml
apiVersion: extensions/v1beta1
kind: Deployment
metadata:
  name: nginx
spec:
  replicas: 2
  template:
    metadata:
      labels:
        app: nginx
    spec:
      containers:
      - name: nginx
        # image: nginx:1.13-alpine   
        image: nginx:1.14-alpine      
```
```bash
$ kubectl apply -f deployments/nginx-deployment.yaml
deployment "nginx" configured
```
When we apply this configuration, like we've already seen with ReplicationController, kubernetes will create a new ReplicaSet, so the old ReplicaSet gradually will diminish the number of running pods while the new will increase it to finally reach desired state. The final state of our cluster will look like this:
 ```bash
$ kubectl get pods --show-labels
NAME                     READY     STATUS    RESTARTS   AGE       LABELS
nginx-5ddc866df9-294cl   1/1       Running   0          7s        app=nginx
nginx-5ddc866df9-99gxt   1/1       Running   0          7s        app=nginx
```
```bash
$ kubectl get replicasets
NAME               DESIRED   CURRENT   READY     AGE
nginx-5ddc866df9   2         2         2         21s
nginx-5f88d57b88   0         0         0         4h 
```
We can check that the version of nginx has actually changed using the `kubectl describe` command, that shows details of a specific resource or group of resources. Search for section `Pod Template.Labels.Containers.nginx.Image: nginx:1.14-alpine` in the command output. We can also notice in `Events` section the rolling update execution logs. 
```bash
$ kubectl describe deployment nginx
Name:                   nginx
Namespace:              default
CreationTimestamp:      Fri, 15 Jun 2018 13:15:15 +0200
Labels:                 app=nginx
Annotations:            deployment.kubernetes.io/revision=2
                        kubectl.kubernetes.io/last-applied-configuration={"apiVersion":"extensions/v1beta1", ...
Selector:               app=nginx
Replicas:               2 desired | 2 updated | 2 total | 2 available | 0 unavailable
StrategyType:           RollingUpdate
MinReadySeconds:        0
RollingUpdateStrategy:  1 max unavailable, 1 max surge
Pod Template:
  Labels:  app=nginx
  Containers:
   nginx:
    Image:        nginx:1.14-alpine
    Port:         <none>
    Environment:  <none>
    Mounts:       <none>
  Volumes:        <none>
Conditions:
  Type           Status  Reason
  ----           ------  ------
  Available      True    MinimumReplicasAvailable
OldReplicaSets:  <none>
NewReplicaSet:   nginx-5ddc866df9 (2/2 replicas created)
Events:
  Type    Reason             Age   From                   Message
  ----    ------             ----  ----                   -------
  Normal  ScalingReplicaSet  39s   deployment-controller  Scaled up replica set nginx-5ddc866df9 to 1
  Normal  ScalingReplicaSet  39s   deployment-controller  Scaled down replica set nginx-5f88d57b88 to 1
  Normal  ScalingReplicaSet  39s   deployment-controller  Scaled up replica set nginx-5ddc866df9 to 2
  Normal  ScalingReplicaSet  38s   deployment-controller  Scaled down replica set nginx-5f88d57b88 to 0
```
Delete the nginx Deployment so that our cluster is clean again.
```bash
$ kubectl delete deployment nginx
deployment "nginx" deleted
```

## ... to Hero!
Now that you we have discovered the basic concepts of kubernetes we are ready to [deploy our first application](../0.1/). 