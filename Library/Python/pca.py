#!/usr/bin/python 
# -*- coding: utf-8 -*-

#主成分分析
#参数为文件名
import sys
import numpy as np
from sklearn.decomposition import PCA

file = sys.argv[1]
data = np.loadtxt(file, delimiter=",")

#把零列的数据清掉
def clearColsData(data):
    std = np.std(data, axis=0, dtype="float16") # axis=0计算每一列的标准差
    emptyIndex = [];
    for i in range(len(std)):
        if std[i] == 0:
            emptyIndex.append(i)
    data = np.delete(data, emptyIndex, axis=1)
    return data
data = clearColsData(data)
#调用sklearn中的PCA，其中主成分有5列
pca_sk = PCA(n_components=2)
#利用PCA进行降维，数据存在newMat中
newMat = pca_sk.fit_transform(data)
for item in newMat:
    print(str(round(item[0], 2)) + ',' + str(round(item[1], 2)))