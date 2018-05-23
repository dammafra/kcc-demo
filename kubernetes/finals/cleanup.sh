#!/bin/bash
kubectl delete configmap backend-configmap
kubectl delete secret credentials-secret
kubectl delete ingress kcc-ingress
kubectl delete deployment backend frontend
kubectl delete service backend-service frontend-service

sed -i '$ d' /cygdrive/c/Windows/System32/drivers/etc/hosts
echo "removed entry 'kcc.local' from hosts file"
