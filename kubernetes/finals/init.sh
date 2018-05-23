#!/bin/bash
kubectl apply -f configmaps/
kubectl apply -f secrets/
kubectl apply -f deployments/
kubectl apply -f services/
kubectl apply -f ingresses/
echo "you have to manually add entry '$(minikube ip) kcc.local' in hosts file"
