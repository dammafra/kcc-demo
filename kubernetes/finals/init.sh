#!/bin/bash
kubectl apply -f configmaps/
kubectl apply -f secrets/
kubectl apply -f deployments/
kubectl apply -f services/
kubectl apply -f ingresses/

echo "$(minikube ip) kcc.local" >> /cygdrive/c/Windows/System32/drivers/etc/hosts
echo "added entry 'kcc.local' in hosts file"
