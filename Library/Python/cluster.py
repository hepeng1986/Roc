#!/usr/bin/python 
# -*- coding: utf-8 -*-

#主成分分析
#参数为文件名
import sys
import numpy as np
from sklearn.decomposition import PCA
from sklearn.cluster import KMeans

file = sys.argv[1]
centers = int(sys.argv[2])
data = np.loadtxt(file, delimiter=",")

#聚类算法
km = KMeans(n_clusters=centers, init='random', random_state=28)
km.fit(data)
y_hat = km.predict(data)

#数据降维
pca_sk = PCA(n_components=2)
#利用PCA进行降维，数据存在newMat中
newMat = pca_sk.fit_transform(data)

#数据输出
for i in range(len(newMat)):
    print(str(km.labels_[i]) + ',' + str(round(newMat[i][0], 2)) + ',' + str(round(newMat[i][1], 2)))