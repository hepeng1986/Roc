#!/usr/bin/python 
# -*- coding: utf-8 -*-

#功效系数法
#参数为文件名
import sys
import numpy as np
from numpy import linalg as LA

file = sys.argv[1]
fieldConf = sys.argv[2]
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

def effect(data, n, fieldConf):
    data = clearColsData(data)
    #计算平均值
    avgData = np.mean(data,axis=0) 
    #计划方差
    std = np.std(data, axis=0) # axis=0计算每一列的标准差
    #变异系数
    dv = std / avgData
    #权重系数
    weight = dv / np.sum(dv)
    #配置满意平均值读取的个数
    algLen = 3
    #algLen = math.ceil(len(data) / 10)
    
    #满意值(最大值)
    satif = [];
    #不允许值(最小值)
    noallow = [];
    for item in data.T:
        item = sorted(item)
        noallow.append(np.mean(item[0:algLen]))
        satif.append(np.mean(item[-1*algLen:]))
    satif = np.array(satif)    
    noallow = np.array(noallow)
    
    hLimit = satif * 1.5
    lLimit = satif * 0.5

    result = []
    for jj in range(len(data)):
        result.append([])
    for i in range(len(data)):
        for j in range(len(data[i])):
            if (j >= len(fieldConf)) or (fieldConf[j] == '1'):       #极大值处理
                if data[i][j] >= satif[j]:
                    value = 100
                else:
                    #极大型变量单项功效系数法=60＋（实际值－不允许值）/（满意值－不允许值）×40
                    value = 60 + (data[i][j] - noallow[j]) / (satif[j] - noallow[j]) * 40
            else:
                if fieldConf[j] == '0':   #值小值处理
                    if data[i][j] <= noallow[j]:
                        value = 100
                    else:
                        #极小型变量单项功效系数法=60＋（实际值－不允许值）/（满意值－不允许值）×40
                        value = 60 + (data[i][j] - noallow[j]) / (satif[j] - noallow[j]) * 40
                        
                else:    #区间型变量处理
                    limit = fieldConf[j].split('-')
                    limit = np.array(limit, dtype = "float16")
                    #区间型变量单项功效系数=100  （当下限值≤实际值≤上限值）
                    if (data[i][j] >= limit[0] and data[i][j] <= limit[1]):
                        value = 100
                    else:
                        #区间型变量单项功效系数=60＋（上限的不允许值－实际值）/（上限的不允许值－上限值）×40   （当实际值 >上限值）
                        if data[i][j] > limit[1]:
                            value = 60 + (hLimit[j] - data[i][j]) / (hLimit[j] - limit[1]) * 40
                        else:
                            #区间型变量单项功效系数=60＋（实际值－下限的不允许值）/（下限值－下限的不允许值）×40   （当实际值＜下限值）
                            if data[i][j] < limit[0]:
                                value = 60 + (data[i][j] - lLimit[j]) / (limit[0] - lLimit[j]) * 40
            result[i].append(value * weight[j])
    rr = np.mean(result, axis=1)
    return rr

data = np.array(X)
if fieldConf:
    fieldConf = fieldConf.split(",")
else:
    fieldConf = []
fieldConf = np.array(fieldConf)
reduced_X = effect(X, 1, fieldConf)

result = list(reduced_X)
result.sort()
result.reverse()

for item in reduced_X:
    print(result.index(item) + 1)