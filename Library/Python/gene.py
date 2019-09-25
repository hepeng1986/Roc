#!/usr/bin/python 
# -*- coding: utf-8 -*-

#因子分析法
#参数为文件名

import numpy as np
import sys

file = sys.argv[1]
X = np.loadtxt(file, delimiter=",")

#把零列的数据清掉
def clearColsData(data):
    std = np.std(data, axis=0, dtype="float16") # axis=0计算每一列的标准差
    emptyIndex = [];
    for i in range(len(std)):
        if std[i] == 0:
            emptyIndex.append(i)
    data = np.delete(data, emptyIndex, axis=1)
    return data

#零均值化
def zeroMean(dataMat):      
    meanVal=np.mean(dataMat,axis=0)     #按列求均值，即求各个特征的均值
    newData=dataMat-meanVal
    return newData,meanVal
 
def pca(dataMat,n):
    dataMat = clearColsData(dataMat)
    
    newData,meanVal=zeroMean(dataMat)
    covMat=np.cov(newData, rowvar=0)    #求协方差矩阵,return ndarray；若rowvar非0，一列代表一个样本，为0，一行代表一个样本
    #covMat=np.corrcoef(newData,rowvar=0) 
    
    eigVals,eigVects=np.linalg.eig(np.mat(covMat))#求特征值和特征向量,特征向量是按列放的，即一列代表一个特征向量
    eigValIndice=np.argsort(eigVals)            #对特征值从小到大排序
    
    n_eigValIndice=eigValIndice[-1:-(n+1):-1]   #最大的n个特征值的下标
    n_eigVect=eigVects[:,n_eigValIndice]        #最大的n个特征值对应的特征向量
    
    lowDDataMat=newData*n_eigVect               #低维特征空间的数据
    
    reconMat=(lowDDataMat*n_eigVect.T)+meanVal  #重构数据
    return lowDDataMat,reconMat

 
dataMat = np.array(X)
reduced_X,mat = pca(X,1)

result = list(reduced_X)
result.sort()
result.reverse()
for item in reduced_X:
    print(result.index(item) + 1)