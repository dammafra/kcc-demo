# Kubernetes Core Concepts Demo
This is the repository of the demo shown during the talk "[Kubernetes Core Concepts](https://www.slideshare.net/FrancescoDammacco/kubernetes-core-concepts-98636754)" at [Club degli Sviluppatori - Puglia](http://www.clubdeglisviluppatori.it/#principi). The aim is to introduce you to the basics concepts behind [Kubernetes](http://kubernetes.io).

## Prerequisites
This demo requires that you have the following installed on your machine:
- [kubectl](https://kubernetes.io/docs/tasks/tools/install-kubectl/), that is the CLI controller app for kubernetes. You will use to interact with all the kubernetes cluster.
- [Minikube](https://github.com/kubernetes/minikube#installation), a simple one-node installation of Kubernetes specifically designed to work on your local machine. Some useful commands for interact with minikube are:
    - `minikube start`, to setup/start the minikube VM.
    - `minikube status`, to check the VM is up.
    - `minikube ssh`, to get into the machine.
    - `minikube stop`, to stop minikube without destroying it.
    - `minikube delete`, to destroy the VM.

## Repository structure
- The [app](app/) folder contains the source code of the application that will be deployed in the second part of the demo.
- The [kubernetes](kubernetes/) folder contains all the configuration files that will be used during the demo, and the steps to reproduce it.

## Application features
The application is composed by a backend written in Node.js and a frontend written in PHP. Both backend and frontend are containerized using Docker. There are two versions of them:

#### Version 0.1
In the first version of the application, the **backend** has two endpoints:
- `/`: the main endpoint, which returns the welcome message `Hello, World!`
- `/hostname`: returns the name of the host that is serving the backend itself

The **frontend**, instead, shows on the page some useful info like the name of the host that is serving it and the values returned by the backend endpoints.

#### Version 0.2
In the second version of the application **backend** : 
- The port on which it is listening is no longer hardcoded, but it's configured by `config.js` file. 
- The main endpoint welcome message now says `Hello, K8s!`.
- One more enpoint is added, `/auth`, which authenticates the frontend with some credentials.

Similarly, the new version of the **frontend** will show the new welcome message and the result of the authentication, with all the info that showed in the previous version.

## Demo Steps
In the very first part of the demo we will explore the basic concepts of kubernetes (ie [Pods](https://kubernetes.io/docs/concepts/workloads/pods/pod/), [ReplicationControllers](https://kubernetes.io/docs/concepts/workloads/controllers/replicationcontroller/), [ReplicaSets](https://kubernetes.io/docs/concepts/workloads/controllers/replicaset/) and [Deployments](https://kubernetes.io/docs/concepts/workloads/controllers/deployment/)). Then, we will deploy and manage our application. Let's start!

#### Chapters
- [From zero...](kubernetes/0.0/)
- [Deploy of application v0.1](kubernetes/0.1/)
- [Deploy of application v0.2](kubernetes/0.2/)