#!/bin/bash
kubectl delete configmap backend-configmap
kubectl delete secret credentials-secret
kubectl delete ingress kcc-ingress
kubectl delete deployment backend frontend
kubectl delete service backend-service frontend-service
echo "you have to manually remove entry '$(minikube ip) kcc.local' from hosts file"
