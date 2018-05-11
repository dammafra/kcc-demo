#!/bin/bash
kubectl delete deployment backend frontend
kubectl delete service backend-service frontend-service
