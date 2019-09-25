#!/usr/bin/python 
# -*- coding: utf-8 -*-

#borda算法

import sys
import numpy as np
from numpy import linalg as LA

file = sys.argv[1]
x = np.loadtxt(file, delimiter=",")

def borda(data_list):
    """波达计数法"""
    result = []
    std = np.std(data_list, axis=1)
    rows, cols = data_list.shape
    for i in range(rows):
        result.append([0, std[i] * -1])
        for m in range(rows):
            tmp = 0
            for n in range(cols):
                if data_list[i][n] < data_list[m][n]:
                    tmp += 1
            if tmp > cols / 2:
                result[i][0] += 1
    return result

result = borda(x)
r1 = list(result)
r1.sort()
rows, cols = x.shape
for item in result:
	print(rows - r1.index(item)) 