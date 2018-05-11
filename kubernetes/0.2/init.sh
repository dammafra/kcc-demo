#!/bin/bash
kubectl apply -f configmaps/
kubectl apply -f secrets/
kubectl apply -f ingresses/
kubectl apply -f deployments/
kubectl apply -f services/

var=$(minikube ip)
echo "$var kcc.local" >> /cygdrive/c/Windows/System32/drivers/etc/hosts
echo "added entry 'kcc.local' in hosts file"
