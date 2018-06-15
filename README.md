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
- In the [app](app/) folder you will find the source code of the application that will be deployed in the second part of the demo.

- In the [kubernetes](kubernetes/) folder you will find all the configuration files that will be used during the demo, and the steps to reproduce it.